<?php

namespace Database\Factories;

use App\Enums\MediaType;
use App\Enums\QuestionType;
use App\Models\Question;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Question>
 */
class QuestionFactory extends Factory
{
    protected $model = Question::class;

    public function definition(): array
    {
        return [
            'type' => QuestionType::Basic,
            'points' => fake()->numberBetween(1, 3),
            'text' => rtrim(fake()->sentence(), '.').'?',
            'explanation' => fake()->sentence(),
            'media_type' => MediaType::None,
            'media_path' => null,
            'is_active' => true,
        ];
    }

    public function specialist(): static
    {
        return $this->state(['type' => QuestionType::Specialist]);
    }

    /** Dołącza komplet wariantów odpowiedzi zgodny z typem pytania. */
    public function withAnswers(): static
    {
        return $this->afterCreating(function (Question $question) {
            if ($question->type === QuestionType::Basic) {
                $question->answers()->createMany([
                    ['label' => 'T', 'text' => 'Tak', 'is_correct' => fake()->boolean(), 'position' => 1],
                    ['label' => 'N', 'text' => 'Nie', 'is_correct' => false, 'position' => 2],
                ]);

                // Zapewnij dokładnie jedną poprawną odpowiedź.
                if ($question->answers()->where('is_correct', true)->doesntExist()) {
                    $question->answers()->where('label', 'N')->update(['is_correct' => true]);
                }

                return;
            }

            $correct = fake()->numberBetween(0, 2);
            $question->answers()->createMany(
                collect(['A', 'B', 'C'])->map(fn ($label, $i) => [
                    'label' => $label,
                    'text' => ucfirst(fake()->words(3, true)),
                    'is_correct' => $i === $correct,
                    'position' => $i + 1,
                ])->all()
            );
        });
    }
}
