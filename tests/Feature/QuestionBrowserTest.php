<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\DrivingCategory;
use App\Models\Question;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuestionBrowserTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        return User::factory()->create(['role' => UserRole::Student, 'email_verified_at' => now()]);
    }

    /** Wyciąga href z przycisku „Baza pytań" (jedyny btn-ghost na tej stronie). */
    private function backButtonHref(string $html): ?string
    {
        preg_match('/<a href="([^"]+)" class="btn btn-ghost btn-sm">/', $html, $m);

        return $m[1] ?? null;
    }

    private function question(array $attrs = [], ?DrivingCategory $category = null): Question
    {
        $cat = $category ?? DrivingCategory::firstOrCreate(
            ['code' => 'B'],
            ['name' => 'Samochód osobowy', 'is_active' => true, 'sort_order' => 1]
        );

        $q = Question::create(array_merge([
            'external_id' => (string) fake()->unique()->numberBetween(1000, 9999),
            'type' => 'basic',
            'points' => 3,
            'text' => 'Przykładowe pytanie?',
            'media_type' => 'none',
            'is_active' => true,
            'source' => 'catalog',
        ], $attrs));

        $q->categories()->attach($cat->id);

        return $q;
    }

    public function test_przycisk_powrotu_prowadzi_do_listy_a_nie_do_poprzedniej_strony(): void
    {
        $user = $this->user();
        $a = $this->question();
        $b = $this->question();

        // Symulacja realnego przepływu: lista → pytanie A → „Następne" → pytanie B.
        $this->actingAs($user)->get(route('questions.index'));
        $this->actingAs($user)->get(route('questions.show', $a));

        $html = $this->actingAs($user)
            ->from(route('questions.show', $a))   // Referer = poprzednie PYTANIE
            ->get(route('questions.show', $b))
            ->assertOk()
            ->getContent();

        // Celujemy w KONKRETNY przycisk powrotu (btn-ghost), a nie w dowolny link —
        // „Poprzednie" ma pełne prawo wskazywać na pytanie A.
        $this->assertSame(
            route('questions.index'),
            $this->backButtonHref($html),
            'Przycisk „Baza pytań" musi prowadzić do listy, nie do poprzednio oglądanego pytania.'
        );
    }

    public function test_przycisk_powrotu_zachowuje_filtry_listy(): void
    {
        $user = $this->user();
        $q = $this->question();
        $cat = DrivingCategory::where('code', 'B')->first();

        // Wejście na listę z filtrami — zapamiętujemy je w sesji.
        $this->actingAs($user)->get(route('questions.index', ['category' => $cat->id, 'type' => 'basic']));

        $html = $this->actingAs($user)->get(route('questions.show', $q))->assertOk()->getContent();
        $back = $this->backButtonHref($html);

        $this->assertStringContainsString('category='.$cat->id, $back);
        $this->assertStringContainsString('type=basic', $back);
    }

    public function test_wejscie_wprost_na_pytanie_bez_historii_daje_czysta_liste(): void
    {
        $html = $this->actingAs($this->user())
            ->get(route('questions.show', $this->question()))
            ->assertOk()
            ->getContent();

        $this->assertSame(route('questions.index'), $this->backButtonHref($html));
    }

    /**
     * Domyślny filtr kategorii zależy od roli. Wcześniej dla wszystkich była zaszyta
     * kat. B, więc kursant kat. C dostawał listę pytań, które go nie dotyczą,
     * a admin — wycinek bazy zamiast całości.
     */
    public function test_kursant_widzi_domyslnie_swoja_kategorie_a_nie_zawsze_B(): void
    {
        $b = DrivingCategory::firstOrCreate(['code' => 'B'], ['name' => 'Osobowe', 'is_active' => true, 'sort_order' => 1]);
        $c = DrivingCategory::firstOrCreate(['code' => 'C'], ['name' => 'Ciężarowe', 'is_active' => true, 'sort_order' => 2]);

        $pytanieB = $this->question(['text' => 'Pytanie tylko dla kategorii B?'], $b);
        $pytanieC = $this->question(['text' => 'Pytanie tylko dla kategorii C?'], $c);

        $kursantC = User::factory()->create(['role' => UserRole::Student, 'driving_category_id' => $c->id]);

        $html = $this->actingAs($kursantC)->get(route('questions.index'))->assertOk()->getContent();

        $this->assertStringContainsString($pytanieC->text, $html);
        $this->assertStringNotContainsString($pytanieB->text, $html, 'Kursant kat. C nie uczy się na pytaniach z B.');
    }

    /** Admin nie uczy się — weryfikuje bazę, więc domyślnie widzi ją w całości. */
    public function test_admin_widzi_domyslnie_wszystkie_kategorie(): void
    {
        $b = DrivingCategory::firstOrCreate(['code' => 'B'], ['name' => 'Osobowe', 'is_active' => true, 'sort_order' => 1]);
        $c = DrivingCategory::firstOrCreate(['code' => 'C'], ['name' => 'Ciężarowe', 'is_active' => true, 'sort_order' => 2]);

        $pytanieB = $this->question(['text' => 'Pytanie tylko dla kategorii B?'], $b);
        $pytanieC = $this->question(['text' => 'Pytanie tylko dla kategorii C?'], $c);

        $html = $this->actingAs(User::factory()->create(['role' => UserRole::Admin]))
            ->get(route('questions.index'))->assertOk()->getContent();

        $this->assertStringContainsString($pytanieB->text, $html);
        $this->assertStringContainsString($pytanieC->text, $html);
        // Filtr ma pokazywać, że nic nie odfiltrowano.
        $this->assertMatchesRegularExpression('/<option value="0"[^>]*selected/', $html);
    }

    /** Jawny wybór w filtrze zawsze wygrywa nad domyślną kategorią roli. */
    public function test_jawny_filtr_wygrywa_nad_domyslna_kategoria(): void
    {
        $b = DrivingCategory::firstOrCreate(['code' => 'B'], ['name' => 'Osobowe', 'is_active' => true, 'sort_order' => 1]);
        $c = DrivingCategory::firstOrCreate(['code' => 'C'], ['name' => 'Ciężarowe', 'is_active' => true, 'sort_order' => 2]);

        $pytanieB = $this->question(['text' => 'Pytanie tylko dla kategorii B?'], $b);
        $pytanieC = $this->question(['text' => 'Pytanie tylko dla kategorii C?'], $c);

        $kursantB = User::factory()->create(['role' => UserRole::Student, 'driving_category_id' => $b->id]);

        // Kursant B prosi o C — dostaje C.
        $html = $this->actingAs($kursantB)->get(route('questions.index', ['category' => $c->id]))
            ->assertOk()->getContent();
        $this->assertStringContainsString($pytanieC->text, $html);
        $this->assertStringNotContainsString($pytanieB->text, $html);

        // …a „wszystkie" (category=0) zdejmuje filtr również jemu.
        $all = $this->actingAs($kursantB)->get(route('questions.index', ['category' => 0]))
            ->assertOk()->getContent();
        $this->assertStringContainsString($pytanieB->text, $all);
        $this->assertStringContainsString($pytanieC->text, $all);
    }

    /**
     * Wyszukiwarka musi patrzeć w tłumaczenie bieżącego języka. Bez tego użytkownik
     * widzący pytania po niemiecku wpisuje niemieckie słowo i nie znajduje nic —
     * bo kolumna `text` trzyma wyłącznie polski oryginał.
     */
    public function test_wyszukiwarka_znajduje_po_tresci_w_jezyku_uzytkownika(): void
    {
        $cat = DrivingCategory::firstOrCreate(['code' => 'B'], ['name' => 'Osobowe', 'is_active' => true, 'sort_order' => 1]);
        $q = $this->question(['text' => 'Jaką odległość należy zachować?'], $cat);
        $q->forceFill(['translations' => [
            'en' => ['q' => 'What distance should be kept?'],
            'de' => ['q' => 'Wie groß soll der Abstand sein?'],
            'ua' => ['q' => 'Яку відстань слід дотримуватися?'],
            'pjm' => ['q' => 'pjm290.wmv'],
        ]])->save();
        $inne = $this->question(['text' => 'Zupełnie inne pytanie o pierwszeństwo?'], $cat);

        $user = User::factory()->create(['role' => UserRole::Student, 'driving_category_id' => $cat->id]);

        // Niemiec szuka niemieckiego słowa.
        $user->forceFill(['locale' => 'de'])->save();
        $html = $this->actingAs($user->fresh())->get(route('questions.index', ['q' => 'Abstand']))->assertOk()->getContent();
        $this->assertStringContainsString('Wie groß soll der Abstand sein?', $html);
        $this->assertStringNotContainsString($inne->text, $html);

        // Ukrainiec — sprawdza przy okazji mapowanie uk → ua.
        $user->forceFill(['locale' => 'uk'])->save();
        $html = $this->actingAs($user->fresh())->get(route('questions.index', ['q' => 'відстань']))->assertOk()->getContent();
        $this->assertStringContainsString('Яку відстань слід дотримуватися?', $html);

        // Polak nadal szuka po oryginale.
        $user->forceFill(['locale' => 'pl'])->save();
        $html = $this->actingAs($user->fresh())->get(route('questions.index', ['q' => 'odległość']))->assertOk()->getContent();
        $this->assertStringContainsString('Jaką odległość należy zachować?', $html);
    }

    /** Wyszukiwarka nie może trafiać w nazwy plików języka migowego. */
    public function test_wyszukiwarka_nie_trafia_w_pliki_jezyka_migowego(): void
    {
        $cat = DrivingCategory::firstOrCreate(['code' => 'B'], ['name' => 'Osobowe', 'is_active' => true, 'sort_order' => 1]);
        $q = $this->question(['text' => 'Pytanie z migowym?'], $cat);
        $q->forceFill(['translations' => ['pjm' => ['q' => 'pjm290.wmv'], 'en' => ['q' => 'A question?']]])->save();

        $user = User::factory()->create(['role' => UserRole::Student, 'driving_category_id' => $cat->id, 'locale' => 'en']);

        $html = $this->actingAs($user)->get(route('questions.index', ['q' => 'wmv']))->assertOk()->getContent();

        $this->assertStringNotContainsString('A question?', $html, 'Szukanie „wmv" nie może zwracać pytań.');
    }
}
