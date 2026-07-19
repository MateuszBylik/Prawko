<?php

namespace Tests\Feature;

use App\Enums\ExamMode;
use App\Enums\ExamStatus;
use App\Enums\UserRole;
use App\Models\Answer;
use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\QuestionUserStat;
use App\Models\User;
use App\Services\ExamForecastService;
use App\Services\StudyPlanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StudyPlanDoseTest extends TestCase
{
    use RefreshDatabase;

    private function user(int $daysToExam = 7): User
    {
        return User::factory()->create([
            'role' => UserRole::Student,
            'email_verified_at' => now(),
            'skill_theta' => 1.0,
            'exam_date' => now()->addDays($daysToExam)->toDateString(),
        ]);
    }

    private function category(): DrivingCategory
    {
        return DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );
    }

    /** @return \Illuminate\Support\Collection<int, Question> */
    private function seedPool(DrivingCategory $cat, int $count = 70)
    {
        return collect(range(1, $count))->map(function ($i) use ($cat) {
            $q = Question::create([
                'external_id' => (string) fake()->unique()->numberBetween(10000, 99999),
                'type' => 'basic', 'points' => 3, 'text' => "Pytanie {$i}?",
                'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
            ]);
            $q->categories()->attach($cat->id);
            Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'A']);
            Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'B']);

            return $q;
        });
    }

    /** Oznacza pytania jako poznane dzisiaj (tak, jak robi to KnowledgeService). */
    private function markSeenToday(User $user, $questions): void
    {
        foreach ($questions as $q) {
            QuestionUserStat::create([
                'user_id' => $user->id, 'question_id' => $q->id,
                'times_seen' => 1, 'times_correct' => 1, 'streak' => 1,
                'stability' => 3.0, 'difficulty' => 5.0, 'fsrs_state' => 2,
                'interval_days' => 3, 'last_reviewed_at' => now(), 'due_at' => now()->addDays(3),
            ]);
        }
    }

    private function plan(User $user, DrivingCategory $cat)
    {
        return app(StudyPlanService::class)->build($user, $cat, app(ExamForecastService::class)->for($user, $cat));
    }

    /**
     * Rdzeń błędu zgłoszonego 2026-07-16: dawka to był `ceil(niewidziane / dni)`,
     * czyli ŚREDNIE TEMPO, więc jedno zrobione pytanie zdejmowało z licznika
     * 1/dni. Przy 7 dniach 40 pytań obniżało „Dziś" o 5 i praca wyglądała
     * na niewidoczną. Dzisiejsza praca musi zdejmować 1:1.
     */
    public function test_zrobione_dzis_pytania_zdejmuja_z_dawki_jeden_do_jednego(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);

        $before = $this->plan($user, $cat);
        $this->assertSame(10, $before->newPerDay, '70 pytań na 7 dni = 10 dziennie.');
        $this->assertSame(10, $before->dailyDose);
        $this->assertSame(0, $before->newDoneToday);

        // Uczymy się dziś 4 nowych pytań.
        $this->markSeenToday($user, $pool->take(4));

        $after = $this->plan($user, $cat);

        $this->assertSame(4, $after->newDoneToday);
        $this->assertSame(10, $after->newPerDay, 'Cel na dziś jest ustalony na początek dnia — nie może zjeżdżać w trakcie.');
        $this->assertSame(6, $after->dailyDose, '4 zrobione pytania muszą zdjąć z dawki dokładnie 4.');
        $this->assertSame(6, $after->newRemaining);
    }

    public function test_zrobienie_calej_dawki_zeruje_licznik_i_oznacza_ja_jako_gotowa(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);

        $this->markSeenToday($user, $pool->take(10));
        $plan = $this->plan($user, $cat);

        $this->assertSame(0, $plan->dailyDose);
        $this->assertTrue($plan->doseDone());
        $this->assertSame(100, $plan->newProgressPercent());

        // Nadmiar nie może zejść poniżej zera ani zepsuć rozmiaru sesji.
        $this->markSeenToday($user, $pool->skip(10)->take(15));
        $over = $this->plan($user, $cat);

        $this->assertSame(0, $over->dailyDose);
        $this->assertSame(0, $over->newRemaining);
        $this->assertGreaterThan(0, $over->sessionSize, 'Po wyrobieniu dawki nadal można uczyć się dalej.');
    }

    public function test_pasek_postepu_odzwierciedla_dzisiejsza_prace(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);

        $this->markSeenToday($user, $pool->take(5));

        $this->assertSame(50, $this->plan($user, $cat)->newProgressPercent(), '5 z 10 = 50%.');
    }

    /** Wczorajsza nauka nie może być liczona jako dzisiejszy postęp. */
    public function test_wczorajsza_nauka_nie_wchodzi_do_dzisiejszego_celu(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);

        $this->markSeenToday($user, $pool->take(20));
        QuestionUserStat::where('user_id', $user->id)->update(['created_at' => now()->subDay()]);

        $plan = $this->plan($user, $cat);

        $this->assertSame(0, $plan->newDoneToday, 'Licznik dnia startuje od zera.');
        // Zostało 50 niewidzianych na 7 dni → 8 dziennie, nic dziś nie zrobione.
        $this->assertSame(8, $plan->newPerDay);
        $this->assertSame(8, $plan->dailyDose);
    }

    /**
     * Zgłoszone 2026-07-16: „zrobiłem dzisiaj dużo pytań, a tempo pokazuje 69,6".
     * Tempo dzieliło przez sztywne 7 dni, choć nauka trwała 2 dni — zaniżało 3,5×.
     * Konsekwencja nie była kosmetyczna: `status()` porównuje tempo z celem, więc
     * użytkownik realnie nadrabiający dostawał etykietę „Trzeba przyspieszyć".
     */
    public function test_tempo_dzieli_przez_realnie_przepracowane_dni_nie_przez_siedem(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);
        $session = $user->examSessions()->create([
            'driving_category_id' => $cat->id, 'mode' => ExamMode::Practice, 'status' => ExamStatus::Completed,
            'total_questions' => 40, 'max_score' => 120, 'started_at' => now(), 'completed_at' => now(),
        ]);

        // 20 odpowiedzi wczoraj + 40 dzisiaj = 60 w ciągu 2 dni nauki → 30/dzień.
        foreach ([[20, now()->subDay()], [40, now()]] as $idx => [$count, $when]) {
            foreach (range(1, $count) as $i) {
                $session->answers()->create([
                    'user_id' => $user->id, 'question_id' => $pool[($idx * 40 + $i) % 70]->id,
                    'position' => $idx * 100 + $i, 'is_correct' => true, 'answered_at' => $when,
                ]);
            }
        }

        $plan = $this->plan($user, $cat);

        $this->assertSame(2, $plan->paceDays, 'Nauka trwa 2 dni, więc dzielimy przez 2.');
        $this->assertEqualsWithDelta(30.0, $plan->recentPace, 0.05, '60 odpowiedzi / 2 dni = 30, a nie 60/7 = 8,6.');
    }

    /** Po tygodniu nauki okno stabilizuje się na 7 dniach i przestaje rosnąć. */
    public function test_tempo_nie_uwzglednia_wiecej_niz_siedmiu_dni(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);
        $session = $user->examSessions()->create([
            'driving_category_id' => $cat->id, 'mode' => ExamMode::Practice, 'status' => ExamStatus::Completed,
            'total_questions' => 1, 'max_score' => 3, 'started_at' => now(), 'completed_at' => now(),
        ]);

        // Nauka od 30 dni: po 7 odpowiedzi dziennie przez ostatnie 7 dni + stara historia.
        foreach (range(0, 29) as $d) {
            $session->answers()->create([
                'user_id' => $user->id, 'question_id' => $pool[$d % 70]->id,
                'position' => $d + 1, 'is_correct' => true, 'answered_at' => now()->subDays($d),
            ]);
        }

        $plan = $this->plan($user, $cat);

        $this->assertSame(7, $plan->paceDays, 'Okno jest siedmiodniowe, mimo 30 dni historii.');
        // W oknie mieści się 7 dni po jednej odpowiedzi → 1/dzień.
        $this->assertEqualsWithDelta(1.0, $plan->recentPace, 0.05);
    }

    public function test_pulpit_pokazuje_ile_zostalo_a_nie_srednie_tempo(): void
    {
        $user = $this->user(daysToExam: 7);
        $cat = $this->category();
        $pool = $this->seedPool($cat, 70);
        $this->markSeenToday($user, $pool->take(4));

        // Karta planu jest ukryta do czasu ukończenia diagnozy (onboarding wygrywa).
        $user->examSessions()->create([
            'driving_category_id' => $cat->id, 'mode' => ExamMode::Diagnosis, 'status' => ExamStatus::Completed,
            'total_questions' => 40, 'max_score' => 74, 'score' => 60, 'correct_count' => 30,
            'started_at' => now()->subHour(), 'completed_at' => now()->subHour(),
        ]);

        $html = $this->actingAs($user)->get(route('dashboard'))->assertOk()->getContent();

        $this->assertStringContainsString('Dziś zostało: 6 pytań', $html);
        $this->assertStringContainsString('4/10 nowych', $html);
    }
}
