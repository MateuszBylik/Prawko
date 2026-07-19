<?php

namespace Database\Seeders;

use App\Enums\MediaType;
use App\Enums\QuestionType;
use App\Models\DrivingCategory;
use App\Models\Question;
use Illuminate\Database\Seeder;

/**
 * Realistyczny bank pytań (PL). Wagi punktowe rozłożone tak, by dało się złożyć
 * pełny arkusz egzaminu dla kategorii B (20 podstawowych + 12 specjalistycznych).
 * Docelowo zastępowalny importem oficjalnej bazy pytań.
 */
class QuestionSeeder extends Seeder
{
    public function run(): void
    {
        // Dla uproszczenia seed przypisuje każde pytanie do głównych kategorii
        // egzaminacyjnych. Realny import zawęziłby zakres pytań specjalistycznych.
        $categoryIds = DrivingCategory::whereIn('code', ['A', 'B', 'C', 'D', 'T'])->pluck('id')->all();

        // ————————————— Pytania podstawowe (TAK / NIE) —————————————
        // [treść, poprawna = TAK?, wyjaśnienie]
        $basic = [
            ['Zbliżając się do oznakowanego przejścia dla pieszych, jesteś obowiązany zachować szczególną ostrożność.', true, 'Art. 26 Prawa o ruchu drogowym nakazuje szczególną ostrożność w rejonie przejścia.'],
            ['Dozwolone jest wyprzedzanie pojazdu na przejściu dla pieszych i bezpośrednio przed nim.', false, 'Na przejściu i bezpośrednio przed nim obowiązuje zakaz wyprzedzania.'],
            ['W obszarze zabudowanym dopuszczalna prędkość samochodu osobowego wynosi 50 km/h.', true, 'Od 2021 r. limit 50 km/h obowiązuje w obszarze zabudowanym przez całą dobę.'],
            ['Sygnał zielony migający oznacza, że za chwilę zostanie nadany sygnał żółty.', true, 'Zielony migający zapowiada zmianę sygnału i zakończenie nadawania zielonego.'],
            ['Kierującemu wolno zatrzymać pojazd na przejściu dla pieszych.', false, 'Zatrzymanie na przejściu dla pieszych jest zabronione.'],
            ['Przed rozpoczęciem wyprzedzania powinieneś upewnić się, że masz odpowiednią widoczność i miejsce.', true, 'To warunek bezpiecznego wykonania manewru wyprzedzania.'],
            ['Pieszy ma pierwszeństwo zarówno wchodząc na przejście, jak i będąc na nim.', true, 'Od 2021 r. pierwszeństwo ma także pieszy wchodzący na przejście.'],
            ['Podczas mgły ograniczającej widoczność należy zwiększyć prędkość, aby szybciej opuścić niebezpieczny odcinek.', false, 'W mgle należy zmniejszyć prędkość i zachować większy odstęp.'],
            ['Kierujący pojazdem jest obowiązany używać pasów bezpieczeństwa podczas jazdy.', true, 'Obowiązek zapinania pasów wynika wprost z przepisów.'],
            ['Zabrania się korzystania z telefonu trzymanego w ręku podczas jazdy.', true, 'Trzymanie telefonu w ręku podczas jazdy jest zabronione.'],
            ['Znak STOP nakazuje zatrzymanie pojazdu nawet wtedy, gdy droga jest wolna.', true, 'Znak B-20 (STOP) bezwzględnie nakazuje zatrzymanie.'],
            ['Na skrzyżowaniu równorzędnym ustępujesz pierwszeństwa pojazdowi nadjeżdżającemu z lewej strony.', false, 'Na skrzyżowaniu równorzędnym pierwszeństwo ma pojazd z prawej strony.'],
            ['Włączając się do ruchu, jesteś obowiązany ustąpić pierwszeństwa innym pojazdom.', true, 'Włączanie się do ruchu wymaga ustąpienia pierwszeństwa.'],
            ['Dozwolone jest zawracanie na autostradzie.', false, 'Na autostradzie zawracanie jest zabronione.'],
            ['W tunelu, podczas jazdy, samochód osobowy powinien utrzymywać odstęp co najmniej 50 m od poprzedzającego pojazdu.', true, 'W tunelu obowiązują minimalne odstępy między pojazdami.'],
            ['Świateł mijania używa się wyłącznie po zmierzchu.', false, 'W Polsce światła mijania (lub do jazdy dziennej) są wymagane przez całą dobę.'],
            ['Dziecko o wzroście poniżej 150 cm należy przewozić w foteliku lub innym urządzeniu przytrzymującym.', true, 'Wymóg dotyczy dzieci poniżej 150 cm wzrostu.'],
            ['Manewr cofania wolno wykonać, o ile nie spowoduje zagrożenia i upewniłeś się o możliwości jego wykonania.', true, 'Cofanie jest dozwolone przy zachowaniu szczególnej ostrożności.'],
            ['Gdy zapory przejazdu kolejowego zaczynają się opuszczać, możesz jeszcze wjechać, jeśli zdążysz przejechać.', false, 'Wjazd na przejazd przy opuszczających się zaporach jest zabroniony.'],
            ['Zbliżając się do wysepki dla pieszych na jezdni, jesteś obowiązany zachować szczególną ostrożność.', true, 'To miejsce wymaga szczególnej ostrożności.'],
            ['Stężenie alkoholu we krwi 0,3 promila oznacza stan po użyciu alkoholu.', true, 'Zakres 0,2–0,5 promila to stan po użyciu alkoholu.'],
            ['Znak A-7 „ustąp pierwszeństwa" zawsze zobowiązuje do zatrzymania pojazdu.', false, 'Znak A-7 nakazuje ustąpić pierwszeństwa, zatrzymanie tylko gdy to konieczne.'],
            ['Kierujący ma obowiązek sygnalizować kierunkowskazem zamiar zmiany kierunku jazdy lub pasa ruchu.', true, 'Sygnalizowanie manewrów jest obowiązkowe.'],
            ['Pojazd z niesprawnym układem hamulcowym można holować wyłącznie na połączeniu sztywnym.', true, 'Przy niesprawnych hamulcach dozwolone jest tylko holowanie na sztywno.'],
            ['Zabrania się wyprzedzania na zakręcie o ograniczonej widoczności.', true, 'Wyprzedzanie na takim zakręcie jest zabronione.'],
            ['Poza obszarem zabudowanym, na drodze jednojezdniowej dwukierunkowej, dopuszczalna prędkość samochodu osobowego wynosi 90 km/h.', true, 'To standardowy limit dla takiej drogi.'],
            ['Podczas postoju na poboczu poza obszarem zabudowanym, w warunkach niedostatecznej widoczności, należy włączyć światła pozycyjne.', true, 'Pojazd należy oznaczyć światłami, by był widoczny.'],
            ['Kierujący rowerem ma prawo korzystać z wyznaczonej drogi dla rowerów.', true, 'Droga dla rowerów jest przeznaczona dla rowerzystów.'],
            ['Na obszarze zabudowanym wolno bez ograniczeń pozostawić pracujący silnik podczas postoju.', false, 'Obowiązuje zakaz zbędnej pracy silnika na postoju w obszarze zabudowanym.'],
            ['Kierujący zbliżający się do skrzyżowania jest obowiązany zachować szczególną ostrożność.', true, 'Skrzyżowanie wymaga szczególnej ostrożności.'],
        ];

        // Wagi punktowe pytań podstawowych (12×3 pkt, 8×2 pkt, 10×1 pkt).
        $basicPoints = array_merge(array_fill(0, 12, 3), array_fill(0, 8, 2), array_fill(0, 10, 1));

        foreach ($basic as $i => [$text, $correctYes, $explanation]) {
            $this->createQuestion(
                type: QuestionType::Basic,
                points: $basicPoints[$i] ?? 1,
                text: $text,
                explanation: $explanation,
                mediaType: $i === 0 ? MediaType::Video : MediaType::None,
                answers: [
                    ['T', 'Tak', $correctYes],
                    ['N', 'Nie', ! $correctYes],
                ],
                categoryIds: $categoryIds,
            );
        }

        // ————————————— Pytania specjalistyczne (A / B / C) —————————————
        // [treść, [A, B, C], indeks poprawnej, wyjaśnienie]
        $specialist = [
            ['Dopuszczalna prędkość samochodu osobowego na autostradzie w Polsce wynosi:', ['120 km/h', '140 km/h', '110 km/h'], 1, 'Na autostradzie limit dla samochodu osobowego to 140 km/h.'],
            ['Dopuszczalna prędkość samochodu osobowego na drodze ekspresowej dwujezdniowej wynosi:', ['100 km/h', '110 km/h', '120 km/h'], 2, 'Na dwujezdniowej ekspresowej obowiązuje 120 km/h.'],
            ['Stan nietrzeźwości zaczyna się od stężenia alkoholu we krwi:', ['powyżej 0,2 ‰', 'powyżej 0,5 ‰', 'powyżej 0,8 ‰'], 1, 'Powyżej 0,5 promila to stan nietrzeźwości.'],
            ['Kartę rowerową musi posiadać kierujący rowerem, który nie ukończył:', ['18 lat', '16 lat', '15 lat'], 0, 'Osoba do 18 lat musi mieć uprawnienie (kartę rowerową lub prawo jazdy).'],
            ['Na skrzyżowaniu o ruchu okrężnym (rondo ze znakami C-12 i A-7) pierwszeństwo ma:', ['pojazd wjeżdżający na rondo', 'pojazd znajdujący się na rondzie', 'zawsze pojazd z prawej'], 1, 'Pierwszeństwo ma pojazd będący już na rondzie.'],
            ['Zabrania się zatrzymania pojazdu przed przejściem dla pieszych w odległości mniejszej niż:', ['10 m', '5 m', '1 m'], 0, 'Zakaz obejmuje 10 m przed przejściem dla pieszych.'],
            ['Tylnych świateł przeciwmgłowych wolno używać, gdy widoczność jest ograniczona do:', ['poniżej 50 m', 'poniżej 100 m', 'poniżej 150 m'], 0, 'Tylne światła przeciwmgłowe – przy widoczności poniżej 50 m.'],
            ['Manewr wyprzedzania to:', ['przejeżdżanie obok pojazdu stojącego', 'przejeżdżanie obok pojazdu jadącego w tym samym kierunku', 'zmiana pasa ruchu'], 1, 'Wyprzedzanie dotyczy pojazdu poruszającego się w tym samym kierunku.'],
            ['Omijanie to przejeżdżanie obok:', ['pojazdu jadącego z naprzeciwka', 'nieporuszającego się pojazdu lub przeszkody', 'pojazdu jadącego w tym samym kierunku'], 1, 'Omijamy obiekt nieruchomy lub przeszkodę.'],
            ['Wymijanie to przejeżdżanie obok:', ['pojazdu jadącego z naprzeciwka', 'przeszkody na jezdni', 'pojazdu poprzedzającego'], 0, 'Wymijanie dotyczy pojazdu jadącego z przeciwnego kierunku.'],
            ['Pojazd uprzywilejowany może nie stosować się do przepisów o ruchu, gdy:', ['zawsze', 'wysyła sygnały świetlne i dźwiękowe oraz zachowuje ostrożność', 'jedzie tylko nocą'], 1, 'Warunkiem jest nadawanie sygnałów i szczególna ostrożność.'],
            ['Postój to unieruchomienie pojazdu na czas:', ['do 1 minuty', 'wynikający z warunków ruchu', 'dłuższy niż 1 minuta'], 2, 'Postój trwa dłużej niż 1 minutę; krócej to zatrzymanie.'],
            ['Dopuszczalna masa całkowita przyczepy lekkiej wynosi:', ['do 750 kg', 'do 1000 kg', 'do 500 kg'], 0, 'Przyczepa lekka ma DMC do 750 kg.'],
            ['Zbliżając się do skrzyżowania z drogą z pierwszeństwem, powinieneś:', ['przyspieszyć', 'ustąpić pierwszeństwa pojazdom na drodze głównej', 'zawsze się zatrzymać'], 1, 'Należy ustąpić pierwszeństwa pojazdom na drodze głównej.'],
            ['W tunelu kierującemu zabrania się:', ['włączania świateł mijania', 'zawracania i cofania', 'używania sygnału dźwiękowego'], 1, 'W tunelu obowiązuje zakaz zawracania i cofania.'],
            ['Znak B-36 oznacza:', ['zakaz zatrzymywania się', 'zakaz postoju', 'zakaz wjazdu'], 0, 'Znak B-36 to zakaz zatrzymywania się.'],
            ['Do kategorii C zalicza się samochód ciężarowy o dopuszczalnej masie całkowitej powyżej:', ['3,5 t', '7,5 t', '2,5 t'], 0, 'Kategoria C dotyczy pojazdów powyżej 3,5 t DMC.'],
            ['Po zauważeniu wypadku w pierwszej kolejności należy:', ['zrobić zdjęcia miejsca zdarzenia', 'zabezpieczyć miejsce zdarzenia', 'spisać oświadczenie'], 1, 'Najpierw zabezpieczamy miejsce, by nie doszło do kolejnego zdarzenia.'],
            ['Dokument potwierdzający zawarcie ubezpieczenia OC należy okazać na żądanie:', ['tylko Policji', 'uprawnionego organu kontroli ruchu drogowego', 'tylko ubezpieczyciela'], 1, 'OC okazujemy uprawnionemu organowi kontroli ruchu drogowego.'],
            ['Podczas jazdy we mgle w dzień samochód osobowy powinien mieć włączone:', ['tylko światła do jazdy dziennej', 'światła mijania', 'światła drogowe'], 1, 'We mgle należy używać świateł mijania (a nie tylko dziennych).'],
        ];

        // Wagi punktowe pytań specjalistycznych (8×3 pkt, 6×2 pkt, 6×1 pkt).
        $specialistPoints = array_merge(array_fill(0, 8, 3), array_fill(0, 6, 2), array_fill(0, 6, 1));

        foreach ($specialist as $i => [$text, $options, $correctIndex, $explanation]) {
            $answers = [];
            foreach ($options as $k => $option) {
                $answers[] = [chr(65 + $k), $option, $k === $correctIndex];
            }

            $this->createQuestion(
                type: QuestionType::Specialist,
                points: $specialistPoints[$i] ?? 1,
                text: $text,
                explanation: $explanation,
                mediaType: $i === 4 ? MediaType::Image : MediaType::None,
                answers: $answers,
                categoryIds: $categoryIds,
            );
        }
    }

    /**
     * @param  array<int, array{0: string, 1: string, 2: bool}>  $answers  [label, text, isCorrect]
     * @param  array<int, int>  $categoryIds
     */
    private function createQuestion(
        QuestionType $type,
        int $points,
        string $text,
        ?string $explanation,
        MediaType $mediaType,
        array $answers,
        array $categoryIds,
    ): void {
        $question = Question::create([
            'type' => $type,
            'points' => $points,
            'text' => $text,
            'explanation' => $explanation,
            'media_type' => $mediaType,
            'is_active' => true,
        ]);

        $position = 1;
        foreach ($answers as [$label, $answerText, $isCorrect]) {
            $question->answers()->create([
                'label' => $label,
                'text' => $answerText,
                'is_correct' => $isCorrect,
                'position' => $position++,
            ]);
        }

        $question->categories()->attach($categoryIds);
    }
}
