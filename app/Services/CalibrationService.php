<?php

namespace App\Services;

use App\Enums\Confidence;
use App\Enums\QuestionType;
use App\Models\Question;
use App\Models\User;
use App\Models\UserAnswer;
use App\Support\Calibration;
use Illuminate\Support\Collection;

/**
 * Statystyki deklarowanej pewności (patrz {@see Calibration} po interpretację).
 * Liczone z historii odpowiedzi na bieżąco — to jedna zagregowana kwerenda,
 * nie ma czego cache'ować.
 */
class CalibrationService
{
    public function for(User $user): Calibration
    {
        // Grupujemy też po typie pytania, bo poziom przypadku zależy od liczby
        // wariantów (TAK/NIE = 50%, A/B/C = 33%) — bez tego trafność strzałów
        // jest liczbą bez punktu odniesienia.
        $rows = UserAnswer::query()
            ->join('questions', 'questions.id', '=', 'user_answers.question_id')
            ->where('user_answers.user_id', $user->id)
            ->whereNotNull('user_answers.answered_at')
            ->whereNotNull('user_answers.confidence')
            ->groupBy('user_answers.confidence', 'user_answers.is_correct', 'questions.type')
            // Aliasy CELOWO inne niż nazwy kolumn modelu: wynik surowego selecta
            // wraca jako UserAnswer, więc `confidence` przeszłoby przez cast na enum
            // i rzutowanie (string) wywaliłoby się na obiekcie. Pod własną nazwą
            // atrybut zostaje takim, jakim przyszedł z bazy.
            ->selectRaw('user_answers.confidence as confidence_value, user_answers.is_correct as correct_value, questions.type as question_type, COUNT(*) as n')
            ->get();

        $sureCorrect = $sureWrong = $guessCorrect = $guessWrong = 0;
        $chanceSum = 0.0;
        $guessTotal = 0;

        foreach ($rows as $row) {
            $n = (int) $row->n;
            $correct = (bool) $row->correct_value;
            $confidence = Confidence::tryFrom((string) $row->confidence_value);

            if ($confidence === Confidence::Sure) {
                $correct ? $sureCorrect += $n : $sureWrong += $n;

                continue;
            }

            if ($confidence !== Confidence::Guess) {
                continue;
            }

            $correct ? $guessCorrect += $n : $guessWrong += $n;

            // Ważony poziom przypadku: 1/liczba wariantów, po tym co faktycznie obstawiał.
            $options = (QuestionType::tryFrom((string) $row->question_type) ?? QuestionType::Specialist)->optionCount();
            $chanceSum += $n / $options;
            $guessTotal += $n;
        }

        return new Calibration(
            sureCorrect: $sureCorrect,
            sureWrong: $sureWrong,
            guessCorrect: $guessCorrect,
            guessWrong: $guessWrong,
            chanceLevel: $guessTotal > 0 ? $chanceSum / $guessTotal : 0.0,
        );
    }

    /**
     * Pytania, w których deklarowałeś „wiem" i pomyliłeś się — od ostatnio
     * spotkanych. Zwijamy do unikalnych pytań (z licznikiem), bo powtórka tego
     * samego haczyka to jeden problem do rozwiązania, nie trzy pozycje na liście.
     *
     * @return Collection<int, array{question: Question, times: int}>
     */
    public function sureMistakes(User $user, int $limit = 6): Collection
    {
        $rows = UserAnswer::query()
            ->where('user_id', $user->id)
            ->where('confidence', Confidence::Sure->value)
            ->where('is_correct', false)
            ->whereNotNull('answered_at')
            ->groupBy('question_id')
            ->selectRaw('question_id, COUNT(*) as times, MAX(answered_at) as last_at')
            ->orderByDesc('last_at')
            ->limit($limit)
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $questions = Question::whereIn('id', $rows->pluck('question_id'))->get()->keyBy('id');

        return $rows
            ->map(fn ($row) => ($q = $questions->get($row->question_id))
                ? ['question' => $q, 'times' => (int) $row->times]
                : null)
            ->filter()
            ->values();
    }
}
