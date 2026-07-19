<?php

namespace App\Services;

use App\Enums\MediaType;
use App\Enums\QuestionType;
use App\Models\Answer;
use App\Models\Question;
use App\Models\QuestionSimilar;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Throwable;

/**
 * Wyjaśnienia pytań generowane przez Claude (Anthropic) — MULTIMODALNIE.
 *
 * Wiele pytań jest wizualnych (znak / sytuacja na zdjęciu lub filmie), a sam tekst
 * bywa ogólny („w tej sytuacji…"). Dlatego do zapytania DOŁĄCZAMY obraz: dla pytań
 * ze zdjęciem — samo zdjęcie, dla filmów — kilka kadrów wyciętych ffmpegiem. Model
 * widzi wtedy konkretną sytuację i tłumaczy szczegółowo, a nie ogólnikami.
 *
 * Klucz architektoniczny: wyjaśnienie jest stałe → generujemy RAZ i zapisujemy w
 * `questions.explanation`. Modelowi podajemy ZWERYFIKOWANĄ poprawną odpowiedź —
 * nie zgaduje, tylko tłumaczy. Bez klucza API funkcja jest wyłączona (zwraca null).
 */
class ExplanationService
{
    /** Ile kadrów wycinamy z filmu (rozłożone, z naciskiem na końcową sytuację). */
    private const VIDEO_FRAMES = 3;

    /** Wyjaśnienie wygenerowane modelem — bez wytycznych o wadzie katalogu. */
    private const SOURCE_AI = 'ai';

    /** Wyjaśnienie wygenerowane JUŻ z wytycznymi dla pytania spornego (patrz `prawko.disputed`). */
    private const SOURCE_AI_DISPUTED = 'ai-disputed';

    /** Czy generowanie AI jest dostępne (jest klucz API). */
    public function enabled(): bool
    {
        return filled(config('services.anthropic.key'));
    }

    /**
     * Zwraca wyjaśnienie pytania: z bazy jeśli jest, w przeciwnym razie generuje
     * (o ile AI włączone) i zapisuje. Zwraca null, gdy brak i nie da się wygenerować.
     */
    public function explain(Question $question, ?Answer $chosen = null): ?string
    {
        $disputed = $this->disputedNote($question) !== null;

        // Wyjaśnienie wygenerowane, ZANIM pytanie oznaczono jako sporne, uzasadnia
        // wadliwą odpowiedź katalogową — jest szkodliwe i musi powstać od nowa, już
        // z wytycznymi. Znacznikiem jest `explanation_source`, więc dopisanie pytania
        // do `prawko.disputed` samo unieważnia starą treść, bez ręcznego czyszczenia.
        // Tekstów ze źródła (`source`) nie ruszamy — nie my je pisaliśmy.
        $stale = $disputed && $question->explanation_source === self::SOURCE_AI;

        if ($question->hasExplanation() && ! $stale) {
            return $question->explanation;
        }

        if (! $this->enabled()) {
            // Bez klucza API nie mamy czym zastąpić szkodliwej treści — lepiej nie
            // pokazać nic niż uzasadnienie odpowiedzi niezgodnej z przepisami.
            return $stale ? null : $question->explanation;
        }

        $text = $this->generate($question, $chosen);
        if ($text === null) {
            return $stale ? null : $question->explanation;
        }

        $question->forceFill([
            'explanation' => $text,
            'explanation_source' => $disputed ? self::SOURCE_AI_DISPUTED : self::SOURCE_AI,
            'explanation_model' => (string) config('services.anthropic.model'),
            'explanation_generated_at' => now(),
        ])->save();

        return $text;
    }

    /** Wywołanie API Claude (z obrazem/kadrami) — zwraca treść wyjaśnienia albo null. */
    public function generate(Question $question, ?Answer $chosen = null): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        $question->loadMissing('answers');
        $correct = $question->answers->firstWhere('is_correct', true);
        if (! $correct) {
            return null;
        }

        $media = $this->mediaBlocks($question);
        $text = $this->userPrompt($question, $correct, $chosen, ! empty($media));
        $content = empty($media) ? $text : array_merge($media, [$this->textBlock($text)]);

        return $this->callApi($this->systemPrompt(), $content);
    }

    /**
     * Wyjaśnienie różnicy w parze mylącej (hybryda Etapu 6): zwraca zapisane albo
     * generuje i zapisuje na wierszu question_similars. Dołącza obrazy obu pytań.
     */
    public function explainDifference(QuestionSimilar $pair): ?string
    {
        if (filled($pair->difference)) {
            return $pair->difference;
        }

        if (! $this->enabled()) {
            return null;
        }

        $a = $pair->question()->with('answers')->first();
        $b = $pair->similarQuestion()->with('answers')->first();
        if (! $a || ! $b) {
            return null;
        }

        $text = $this->callApi($this->differenceSystemPrompt(), $this->differenceContent($a, $b));
        if ($text === null) {
            return null;
        }

        $pair->forceFill([
            'difference' => $text,
            'difference_model' => (string) config('services.anthropic.model'),
            'difference_generated_at' => now(),
        ])->save();

        return $text;
    }

    /**
     * Wspólne wywołanie Messages API. $content: zwykły tekst albo tablica bloków
     * (tekst + obrazy). Zwraca tekst odpowiedzi albo null przy błędzie/braku klucza.
     *
     * @param  string|array<int, array<string, mixed>>  $content
     */
    private function callApi(string $system, string|array $content): ?string
    {
        if (! $this->enabled()) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => (string) config('services.anthropic.key'),
                'anthropic-version' => (string) config('services.anthropic.version'),
                'content-type' => 'application/json',
            ])
                ->timeout((int) config('services.anthropic.timeout'))
                ->post(rtrim((string) config('services.anthropic.base_url'), '/').'/v1/messages', [
                    'model' => (string) config('services.anthropic.model'),
                    'max_tokens' => (int) config('services.anthropic.max_tokens'),
                    'system' => $system,
                    'messages' => [['role' => 'user', 'content' => $content]],
                ]);

            if (! $response->successful()) {
                Log::warning('Anthropic API błąd', ['status' => $response->status(), 'body' => $response->body()]);

                return null;
            }

            $text = $response->json('content.0.text');

            return is_string($text) && trim($text) !== '' ? trim($text) : null;
        } catch (Throwable $e) {
            Log::warning('Anthropic API wyjątek: '.$e->getMessage());

            return null;
        }
    }

    // ===== Budowanie treści =====

    private function textBlock(string $text): array
    {
        return ['type' => 'text', 'text' => $text];
    }

    /** Treść dla różnicy pary — z obrazami obu pytań (jeśli są), inaczej sam tekst. */
    private function differenceContent(Question $a, Question $b): string|array
    {
        $mediaA = $this->mediaBlocks($a);
        $mediaB = $this->mediaBlocks($b);
        $text = $this->differenceUserPrompt($a, $b, ! empty($mediaA) || ! empty($mediaB));

        if (empty($mediaA) && empty($mediaB)) {
            return $text;
        }

        $blocks = [];
        if ($mediaA) {
            $blocks[] = $this->textBlock('Obraz / kadry do pytania A:');
            $blocks = array_merge($blocks, $mediaA);
        }
        if ($mediaB) {
            $blocks[] = $this->textBlock('Obraz / kadry do pytania B:');
            $blocks = array_merge($blocks, $mediaB);
        }
        $blocks[] = $this->textBlock($text);

        return $blocks;
    }

    // ===== Media → bloki obrazów =====

    /** @return array<int, array<string, mixed>> bloki obrazów dla pytania (zdjęcie lub kadry filmu) */
    private function mediaBlocks(Question $question): array
    {
        $path = $this->mediaFilePath($question);
        if ($path === null) {
            return [];
        }

        return match ($question->media_type) {
            MediaType::Image => ($block = $this->imageBlock($path)) ? [$block] : [],
            MediaType::Video => $this->videoFrameBlocks($path),
            default => [],
        };
    }

    /** Ścieżka pliku mediów na dysku (zdjęcie w cz. 1/cz. 2, film jako mp4). */
    private function mediaFilePath(Question $question): ?string
    {
        if (! $question->media_path) {
            return null;
        }

        $base = storage_path('app/import/media');

        if ($question->media_type === MediaType::Image) {
            foreach (['cz. 1', 'cz. 2'] as $sub) {
                $path = $base.'/'.$sub.'/'.$question->media_path;
                if (is_file($path)) {
                    return $path;
                }
            }
        }

        if ($question->media_type === MediaType::Video) {
            $mp4 = pathinfo($question->media_path, PATHINFO_FILENAME).'.mp4';
            $path = $base.'/mp4/'.$mp4;
            if (is_file($path)) {
                return $path;
            }
        }

        return null;
    }

    private function imageBlock(string $path): ?array
    {
        $data = @file_get_contents($path);
        if ($data === false) {
            return null;
        }

        return [
            'type' => 'image',
            'source' => ['type' => 'base64', 'media_type' => $this->imageMime($path), 'data' => base64_encode($data)],
        ];
    }

    private function imageMime(string $path): string
    {
        return match (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            default => 'image/jpeg',
        };
    }

    /** Wycina kilka kadrów z filmu i zwraca je jako bloki obrazów. */
    private function videoFrameBlocks(string $mp4): array
    {
        $duration = $this->videoDuration($mp4);
        $tmp = storage_path('app/tmp');
        if (! is_dir($tmp)) {
            @mkdir($tmp, 0775, true);
        }

        // Rozłożone kadry z naciskiem na końcową sytuację (na niej opiera się odpowiedź).
        $fractions = $duration > 0 ? [0.40, 0.70, 0.95] : [0.0];
        $blocks = [];
        foreach (array_slice($fractions, 0, self::VIDEO_FRAMES) as $i => $frac) {
            $out = $tmp.'/frm_'.getmypid().'_'.uniqid().'.jpg';
            if ($this->extractFrame($mp4, $duration * $frac, $out) && is_file($out)) {
                $data = @file_get_contents($out);
                @unlink($out);
                if ($data !== false) {
                    $blocks[] = [
                        'type' => 'image',
                        'source' => ['type' => 'base64', 'media_type' => 'image/jpeg', 'data' => base64_encode($data)],
                    ];
                }
            }
        }

        return $blocks;
    }

    private function videoDuration(string $mp4): float
    {
        try {
            $r = Process::timeout(20)->run([
                'ffprobe', '-v', 'error', '-show_entries', 'format=duration',
                '-of', 'default=nw=1:nk=1', $mp4,
            ]);

            return $r->successful() ? (float) trim($r->output()) : 0.0;
        } catch (Throwable) {
            return 0.0;
        }
    }

    private function extractFrame(string $mp4, float $seconds, string $out): bool
    {
        try {
            $r = Process::timeout(30)->run([
                'ffmpeg', '-y', '-loglevel', 'error',
                '-ss', (string) round(max(0.0, $seconds), 2), '-i', $mp4,
                '-frames:v', '1', '-vf', 'scale=768:-2', '-q:v', '5', $out,
            ]);

            return $r->successful();
        } catch (Throwable) {
            return false;
        }
    }

    // ===== Prompty =====

    private function systemPrompt(): string
    {
        return implode(' ', [
            'Jesteś doświadczonym instruktorem nauki jazdy w Polsce.',
            'Wyjaśniasz krótko i rzeczowo, po polsku, dlaczego wskazana odpowiedź w teście na prawo jazdy jest poprawna, a błędna — niepoprawna.',
            'Jeśli dołączono obraz lub kadry z filmu, KONIECZNIE odnieś się do konkretnej sytuacji na nich widocznej (znaki, sygnalizacja, pojazdy, oznaczenia poziome, geometria skrzyżowania) — nie odpowiadaj ogólnikami.',
            'Opierasz się na zasadach ruchu drogowego. NIE wymyślaj numerów artykułów ani paragrafów — jeśli nie masz pewności co do przepisu, wyjaśnij samą zasadę.',
            'Maksymalnie 3–4 zdania. Zwykły tekst, bez nagłówków i formatowania markdown.',
        ]);
    }

    /**
     * Zastrzeżenie do odpowiedzi katalogowej (config `prawko.disputed`), jeśli pytanie
     * jest oznaczone jako sporne. Ta sama konfiguracja zasila tabliczkę w widokach —
     * jedno źródło prawdy, żeby wyjaśnienie nie przeczyło temu, co widać obok.
     *
     * @return array{catalog_answer:string, basis:string, note:string}|null
     */
    private function disputedNote(Question $question): ?array
    {
        $note = config('prawko.disputed.'.$question->external_id);

        return is_array($note) ? $note : null;
    }

    private function userPrompt(Question $question, Answer $correct, ?Answer $chosen, bool $hasMedia): string
    {
        $isSpecialist = $question->type === QuestionType::Specialist;
        $disputed = $this->disputedNote($question);

        $lines = [];
        if ($hasMedia) {
            $lines[] = 'Sytuacja z pytania jest widoczna na załączonym obrazie/kadrach — opisz, co istotne widać, i na tym oprzyj wyjaśnienie.';
            $lines[] = '';
        }
        $lines[] = 'Pytanie: '.$question->text;
        $lines[] = '';
        $lines[] = 'Warianty odpowiedzi:';
        foreach ($question->answers as $a) {
            $prefix = $isSpecialist ? strtoupper((string) $a->label).'. ' : '- ';
            $lines[] = $prefix.$a->text;
        }
        $lines[] = '';

        // Pytanie sporne: NIE wolno kazać modelowi uzasadniać odpowiedzi, która jest
        // niezgodna z przepisami — postawiony przed takim poleceniem konfabuluje
        // (tak powstało wyjaśnienie do #10899). Podajemy gotowe uzasadnienie wady
        // i zakazujemy własnej interpretacji przepisu.
        if ($disputed) {
            $lines[] = 'UWAGA — PYTANIE SPORNE (WADLIWE).';
            $lines[] = 'Odpowiedź katalogowa Ministerstwa Infrastruktury, punktowana na egzaminie: '.$correct->text;
            $lines[] = 'Ta odpowiedź jest NIEZGODNA Z PRZEPISAMI. Na czym polega błąd katalogu:';
            $lines[] = $disputed['note'];
            $lines[] = 'Podstawa prawna: '.$disputed['basis'];
            $lines[] = '';
            $lines[] = 'Napisz wyjaśnienie, które: (1) mówi, że katalog i egzamin oczekują odpowiedzi '
                .strtoupper((string) $disputed['catalog_answer']).' i taką należy zaznaczyć na egzaminie, '
                .'(2) zwięźle wskazuje, na czym polega błąd katalogu — WYŁĄCZNIE na podstawie uzasadnienia podanego wyżej.';
            $lines[] = 'ZAKAZY: nie uzasadniaj, że odpowiedź katalogowa jest zgodna z przepisami (nie jest); '
                .'nie twórz własnej interpretacji przepisu; nie podawaj innych artykułów niż wskazany wyżej; '
                .'nie sugeruj, że pozostałe warianty są poprawne.';

            return implode("\n", $lines);
        }

        $lines[] = 'Poprawna odpowiedź: '.$correct->text;

        if ($chosen && ! $chosen->is_correct) {
            $lines[] = 'Odpowiedź wybrana przez uczącego się (błędna): '.$chosen->text;
            $lines[] = '';
            $lines[] = 'Wyjaśnij zwięźle, dlaczego poprawna odpowiedź jest poprawna i dlaczego wybrana odpowiedź jest błędna.';
        } else {
            $lines[] = '';
            $lines[] = 'Wyjaśnij zwięźle, dlaczego poprawna odpowiedź jest poprawna.';
        }

        return implode("\n", $lines);
    }

    private function differenceSystemPrompt(): string
    {
        return implode(' ', [
            'Jesteś doświadczonym instruktorem nauki jazdy w Polsce.',
            'Dostajesz dwa podobnie brzmiące pytania egzaminacyjne wraz z poprawnymi odpowiedziami, a często też ich obrazy/kadry.',
            'Wyjaśnij zwięźle po polsku, czym te pytania (i przedstawione sytuacje) się różnią i na co zwrócić uwagę, żeby ich nie pomylić.',
            'Jeśli dołączono obrazy, odnieś się do konkretnych różnic w sytuacji (znaki, ustawienie pojazdów, geometria).',
            'Jeśli różnią się tylko sformułowaniem, a sens i odpowiedź są takie same — napisz to wprost.',
            'NIE wymyślaj numerów artykułów. Maksymalnie 3 zdania, zwykły tekst bez formatowania markdown.',
        ]);
    }

    private function differenceUserPrompt(Question $a, Question $b, bool $hasMedia): string
    {
        $correctA = $a->answers->firstWhere('is_correct', true);
        $correctB = $b->answers->firstWhere('is_correct', true);

        $lines = [];
        if ($hasMedia) {
            $lines[] = 'Obrazy/kadry powyżej przedstawiają sytuacje z pytań A i B — uwzględnij różnice widoczne na nich.';
            $lines[] = '';
        }
        $lines[] = 'Pytanie A: '.$a->text;
        $lines[] = 'Poprawna odpowiedź A: '.($correctA->text ?? '—');
        $lines[] = '';
        $lines[] = 'Pytanie B: '.$b->text;
        $lines[] = 'Poprawna odpowiedź B: '.($correctB->text ?? '—');
        $lines[] = '';
        $lines[] = 'Czym różnią się te dwa pytania i na co uważać, żeby ich nie pomylić?';

        return implode("\n", $lines);
    }
}
