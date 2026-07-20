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
 * Regresje ekranu egzaminu zgłoszone 2026-07-21:
 *  - pytania SPECJALISTYCZNE ze zdjęciem nie pokazywały go w ogóle (widok miał
 *    warunek `! $isSpecialist`, a 774 z nich ma foto),
 *  - materiał ma być w ramce 16:9 (zdjęcia i filmy są 1024×576), nie 4:3,
 *  - pod materiałem mają być WYŁĄCZNIE pytanie i odpowiedzi — bez plakietki typu
 *    i bez numeru pytania „x / total".
 *
 * `Question::imageUrl()` nie sprawdza istnienia pliku (w przeciwieństwie do wideo),
 * więc sam `media_type=image` + `media_path` wystarczą, by URL trafił do HTML.
 */
class ExamMediaTest extends TestCase
{
    use RefreshDatabase;

    private function examWith(string $type, string $mediaType, ?string $mediaPath): array
    {
        $user = User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);
        $cat = DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );

        $q = Question::create([
            'external_id' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => $type, 'points' => 2, 'text' => 'Co powinien zrobić kierujący?',
            'media_type' => $mediaType, 'media_path' => $mediaPath,
            'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->categories()->attach($cat->id);

        if ($type === 'specialist') {
            Answer::create(['question_id' => $q->id, 'text' => 'A', 'is_correct' => true, 'label' => 'A']);
            Answer::create(['question_id' => $q->id, 'text' => 'B', 'is_correct' => false, 'label' => 'B']);
            Answer::create(['question_id' => $q->id, 'text' => 'C', 'is_correct' => false, 'label' => 'C']);
        } else {
            Answer::create(['question_id' => $q->id, 'text' => 'Tak', 'is_correct' => true, 'label' => 'T']);
            Answer::create(['question_id' => $q->id, 'text' => 'Nie', 'is_correct' => false, 'label' => 'N']);
        }

        $session = ExamSession::create([
            'user_id' => $user->id, 'driving_category_id' => $cat->id,
            'mode' => ExamMode::Exam, 'status' => ExamStatus::InProgress,
            'total_questions' => 1, 'max_score' => 2, 'started_at' => now(),
        ]);
        $session->answers()->create(['user_id' => $user->id, 'question_id' => $q->id, 'position' => 1]);

        return [$user, $session, $q];
    }

    private function render(User $user, ExamSession $session): string
    {
        return $this->actingAs($user)->get(route('sessions.question', [$session, 1]))->assertOk()->getContent();
    }

    public function test_pytanie_specjalistyczne_ze_zdjeciem_pokazuje_je_na_egzaminie(): void
    {
        [$user, $session, $q] = $this->examWith('specialist', 'image', 'spec-foto.jpg');

        $html = $this->render($user, $session);

        // Główna regresja: URL zdjęcia MUSI trafić do HTML (wcześniej blokowane).
        $this->assertStringContainsString($q->imageUrl(), $html);
        $this->assertMatchesRegularExpression('/<img[^>]+object-cover/', $html);
    }

    public function test_material_egzaminu_jest_w_ramce_16_9(): void
    {
        // Zdjęcie
        [$user, $session] = $this->examWith('specialist', 'image', 'spec-foto.jpg');
        $this->assertStringContainsString('aspect-video', $this->render($user, $session));

        // Brak mediów (specjalistyczne bez foto) — placeholder też 16:9.
        [$user2, $session2] = $this->examWith('specialist', 'image', null);
        $this->assertStringContainsString('aspect-video', $this->render($user2, $session2));
    }

    public function test_pod_materialem_nie_ma_plakietki_typu_ani_numeru_pytania(): void
    {
        [$user, $session] = $this->examWith('specialist', 'image', 'spec-foto.jpg');

        $html = $this->render($user, $session);

        // Plakietka typu (badge-amber) i numer „1 / 1" były pod materiałem — mają zniknąć.
        $this->assertStringNotContainsString('badge-amber', $html);
        $this->assertStringNotContainsString('1 / 1', $html);
    }
}
