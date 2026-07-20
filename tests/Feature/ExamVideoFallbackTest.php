<?php

namespace Tests\Feature;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\ExamSession;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UWAGA co do zakresu: to test DYMNY, nie behawioralny.
 *
 * Logika nadzorcy filmu żyje w Alpine (JS), a projekt nie ma runnera JS ani Dusk,
 * więc nie da się tu sprawdzić, że przy zaciętym filmie faza faktycznie przechodzi
 * do odpowiedzi. Test pilnuje tylko, że zabezpieczenia są wpięte w widok — czyli
 * chroni przed ich cichym usunięciem przy refaktorze, a nie dowodzi poprawności.
 */
class ExamVideoFallbackTest extends TestCase
{
    use RefreshDatabase;

    /** Nazwa pliku-atrapy; `Question::videoUrl()` sprawdza wyłącznie `is_file()`. */
    private const FAKE_MP4 = 'test-exam-video-fallback.mp4';

    private function mp4Path(): string
    {
        return storage_path('app/import/media/mp4/'.self::FAKE_MP4);
    }

    protected function tearDown(): void
    {
        // Test pisze do prawdziwego katalogu mediów — musi po sobie posprzątać
        // niezależnie od wyniku, żeby nie zaśmiecić produkcyjnego storage.
        if (is_file($this->mp4Path())) {
            unlink($this->mp4Path());
        }

        parent::tearDown();
    }

    private function examWithVideoQuestion(): array
    {
        // `videoUrl()` zwraca null bez istniejącego pliku, a wtedy widok w ogóle nie
        // renderuje <video> — atrapa musi istnieć, choć jej treść jest bez znaczenia.
        @mkdir(dirname($this->mp4Path()), 0o775, true);
        file_put_contents($this->mp4Path(), 'atrapa');

        $user = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);
        $cat = DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );

        $q = Question::create([
            'external_id' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => 'basic', 'points' => 3, 'text' => 'Czy w tej sytuacji wolno wyprzedzać?',
            'media_type' => 'video', 'media_path' => 'test-exam-video-fallback.wmv',
            'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($cat->id);
        Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T']);
        Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'N']);

        $session = ExamSession::create([
            'user_id' => $user->id, 'driving_category_id' => $cat->id,
            'mode' => ExamMode::Exam, 'status' => ExamStatus::InProgress,
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now(),
        ]);
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        return [$user, $session];
    }

    /**
     * Zgłoszone 2026-07-17: „film się kończy, ale nie pojawia się pole na odpowiedź".
     * Faza 'playing' miała jedyne wyjście przez zdarzenie `ended` — gdy ono nie
     * dochodziło, egzamin wisiał bez żadnego timera i bez możliwości odpowiedzi.
     */
    public function test_faza_filmu_ma_wyjscie_awaryjne_poza_zdarzeniem_ended(): void
    {
        [$user, $session] = $this->examWithVideoQuestion();

        $html = $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();

        // Nadzorca musi być wywoływany z pętli zegara, inaczej nie ma go kto uruchomić.
        $this->assertStringContainsString("phase === 'playing'", $html);
        $this->assertStringContainsString('watchFilm()', $html);

        // Regresja: cały obiekt x-data musi zmieścić się w JEDNYM atrybucie. Gdy
        // wewnątrz trafi się znak " (np. w komentarzu), atrybut urywa się, Alpine
        // nie startuje, a reszta kodu JS wysypuje się jako tekst na stronę. Skoro
        // `finishExam` jest blisko końca obiektu, jego obecność w nieprzerwanym
        // `x-data="[^"]*"` dowodzi, że atrybut nie został ucięty w środku.
        $this->assertMatchesRegularExpression('/x-data="[^"]*finishExam/', $html);

        // Awarie w połowie strumienia: `play().catch()` łapie tylko brak startu.
        $this->assertStringContainsString('x-on:error="filmEnded()"', $html);
        $this->assertStringContainsString('x-on:abort="filmEnded()"', $html);
        $this->assertStringContainsString('x-on:ended="filmEnded()"', $html);
    }

    /** Egzamin nie zbiera pewności — pilnujemy, że fala zmian z nauki tu nie przecieka. */
    public function test_egzamin_nie_pyta_o_pewnosc(): void
    {
        [$user, $session] = $this->examWithVideoQuestion();

        $html = $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();

        $this->assertStringNotContainsString('name="confidence"', $html);
    }
}
