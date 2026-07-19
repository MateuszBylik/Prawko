<?php

namespace App\Models;

use App\Enums\MediaType;
use App\Enums\QuestionType;
use App\Enums\TrapType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Question extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'external_id', 'type', 'points', 'text', 'explanation',
        'explanation_source', 'explanation_model', 'explanation_generated_at',
        'traps', 'translations', 'media_type', 'media_path', 'difficulty', 'irt_b',
        'is_active', 'source',
    ];

    protected function casts(): array
    {
        return [
            'type' => QuestionType::class,
            'media_type' => MediaType::class,
            'translations' => 'array',
            'is_active' => 'boolean',
            'points' => 'integer',
            'difficulty' => 'integer',
            'irt_b' => 'float', // b_q — trudność pytania (skala logit)
            'explanation_generated_at' => 'datetime',
            'traps' => 'array',
        ];
    }

    // ————————————————— Tłumaczenia treści —————————————————

    /**
     * Klucz, pod którym w `translations` leżą tłumaczenia dla języka interfejsu.
     * Null = polski, czyli tekst bazowy z kolumny `text` (arkusz MI nie zawiera
     * polskiego jako tłumaczenia — polski JEST oryginałem).
     *
     * Mapowanie mieszka w config/locales.php, bo locale `uk` odpowiada kluczowi
     * danych `ua`. To jedyne miejsce styku tych dwóch nazewnictw.
     */
    private static function translationKey(): ?string
    {
        return config('locales.'.app()->getLocale().'.questions_key');
    }

    /**
     * Treść pytania w języku interfejsu, z odbiciem na polski.
     *
     * Odbicie jest istotne: arkusz MI ma tłumaczenia tylko dla części pytań, więc
     * brak wpisu to normalny stan, a nie awaria — lepiej pokazać polską treść niż
     * puste pytanie.
     */
    public function localizedText(): string
    {
        $key = self::translationKey();

        return $key ? ($this->translations[$key]['q'] ?? $this->text) : $this->text;
    }

    /**
     * Treść wariantu odpowiedzi w języku interfejsu.
     *
     * Dwa różne przypadki:
     *  - specjalistyczne (A/B/C) — tłumaczenia leżą w `translations[$lang][a|b|c]`,
     *  - podstawowe (T/N) — arkusz NIE tłumaczy „Tak/Nie” (w `translations` jest samo
     *    `q`), więc bierzemy je z plików językowych.
     */
    public function localizedAnswerText(Answer $answer): string
    {
        $label = mb_strtolower((string) $answer->label);

        if (in_array($label, ['t', 'n'], true)) {
            return __('enums.answer.'.($label === 't' ? 'yes' : 'no'));
        }

        $key = self::translationKey();

        return $key ? ($this->translations[$key][$label] ?? $answer->text) : $answer->text;
    }

    /**
     * Warianty językowe treści do sekcji „tłumaczenia" na karcie pytania.
     *
     * Zasada: POLSKI zawsze pierwszy (to oryginał), potem en/de/uk. Pomijamy język
     * aktualnie wyświetlany (jest już w treści głównej pytania) oraz języki bez
     * tłumaczenia — arkusz MI tłumaczy tylko część pytań.
     *
     * Wymaga wczytanej relacji `answers` (karta pytania i tak po niej iteruje).
     *
     * @return array<int, array{locale: string, name: string, q: string, options: array<string, string>}>
     */
    public function languageVariants(): array
    {
        $current = app()->getLocale();

        // locale => klucz danych w `translations` (pl = null → kolumny bazowe).
        $langs = ['pl' => null, 'en' => 'en', 'de' => 'de', 'uk' => 'ua'];

        $out = [];
        foreach ($langs as $locale => $key) {
            if ($locale === $current) {
                continue; // ten język widać już w treści głównej
            }

            $q = $key === null ? $this->text : ($this->translations[$key]['q'] ?? null);
            if (blank($q)) {
                continue;
            }

            // Warianty A/B/C (pytania specjalistyczne). „Tak/Nie” pomijamy — arkusz
            // ich nie tłumaczy, a i tak są w plikach językowych.
            $options = [];
            foreach ($this->answers as $answer) {
                $label = mb_strtoupper((string) $answer->label);
                if (! in_array($label, ['A', 'B', 'C'], true)) {
                    continue;
                }
                $text = $key === null
                    ? $answer->text
                    : ($this->translations[$key][mb_strtolower($label)] ?? null);
                if (filled($text)) {
                    $options[$label] = $text;
                }
            }

            $out[] = [
                'locale' => $locale,
                'name' => __('questions.show.lang_'.$locale),
                'q' => $q,
                'options' => $options,
            ];
        }

        return $out;
    }

    /** Wykryte typy pułapek jako enum TrapType[]. */
    public function trapTypes(): array
    {
        return array_values(array_filter(array_map(
            fn ($t) => TrapType::tryFrom($t),
            $this->traps ?? []
        )));
    }

    public function scopeWithTrap(Builder $query, string $trap): Builder
    {
        return $query->whereJsonContains('traps', $trap);
    }

    /** Czy pytanie ma już zapisane wyjaśnienie (źródłowe lub wygenerowane). */
    public function hasExplanation(): bool
    {
        return filled($this->explanation);
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class)->orderBy('position');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(DrivingCategory::class, 'category_question');
    }

    public function userAnswers(): HasMany
    {
        return $this->hasMany(UserAnswer::class);
    }

    /** Pytania podobne (pary mylące), od najbardziej podobnego. */
    public function similars(): HasMany
    {
        return $this->hasMany(QuestionSimilar::class, 'question_id')->orderByDesc('score');
    }

    /** Poprawny wariant odpowiedzi (wymaga załadowanej relacji answers). */
    public function correctAnswer(): ?Answer
    {
        return $this->answers->firstWhere('is_correct', true);
    }

    /** URL zdjęcia (serwowanego spoza public/ przez media.show) albo null. */
    public function imageUrl(): ?string
    {
        if ($this->media_type === MediaType::Image && $this->media_path) {
            return route('media.show', ['name' => $this->media_path]);
        }

        return null;
    }

    /** URL przekonwertowanego MP4, jeśli plik już istnieje, albo null. */
    public function videoUrl(): ?string
    {
        if ($this->media_type === MediaType::Video && $this->media_path) {
            $mp4 = pathinfo($this->media_path, PATHINFO_FILENAME).'.mp4';
            if (is_file(storage_path('app/import/media/mp4/'.$mp4))) {
                return route('media.show', ['name' => $mp4]);
            }
        }

        return null;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOfType(Builder $query, QuestionType $type): Builder
    {
        return $query->where('type', $type->value);
    }

    public function scopeForCategory(Builder $query, int $categoryId): Builder
    {
        return $query->whereHas(
            'categories',
            fn (Builder $q) => $q->where('driving_categories.id', $categoryId)
        );
    }
}
