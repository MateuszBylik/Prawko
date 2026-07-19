<?php

namespace App\Http\Controllers;

use App\Enums\MediaType;
use App\Enums\QuestionType;
use App\Enums\TrapType;
use App\Models\DrivingCategory;
use App\Models\Question;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Przeglądarka bazy pytań — do samodzielnej nauki (podgląd pytań, odpowiedzi
 * i materiałów). Egzamin/sesje obsługuje osobno ExamSessionController.
 */
class QuestionController extends Controller
{
    public function index(Request $request): View
    {
        // Zapamiętujemy filtry listy, żeby przycisk „Baza pytań" na stronie pytania
        // wracał dokładnie tam, skąd przyszedłeś — nawet po przejściu przez kilka
        // pytań przyciskami „Następne" (wcześniej używał Referera, który po drodze
        // wskazywał już na poprzednie pytanie, a nie na listę).
        $request->session()->put('questions.list_query', $request->getQueryString());

        $categories = DrivingCategory::active()->orderBy('sort_order')->get();

        /*
         * Domyślny filtr kategorii zależy od roli — wcześniej dla wszystkich była
         * zaszyta na sztywno kategoria B:
         *
         *  - kursant → jego własna kategoria z konta. Uczy się na jednej i tylko ona
         *    wchodzi do jego prognozy; pytania z innych kategorii byłyby szumem
         *    (a kursant kat. C dostawał listę, która go nie dotyczy).
         *  - admin → BEZ filtra. Nie uczy się, tylko weryfikuje bazę, i to całą,
         *    a nie jedną kategorię.
         *
         * Jawny `?category=` zawsze wygrywa nad domyślną — puste = „wszystkie".
         */
        $categoryId = $request->has('category')
            ? $request->integer('category')
            : ($request->user()->learns() ? $request->user()->driving_category_id : null);

        $type = $request->string('type')->lower()->toString();
        $media = $request->string('media')->lower()->toString();
        $trap = $request->string('trap')->lower()->toString();
        $search = trim((string) $request->string('q'));

        $query = Question::query()->active()->with('categories');

        if ($categoryId) {
            $query->forCategory($categoryId);
        }
        if (in_array($type, [QuestionType::Basic->value, QuestionType::Specialist->value], true)) {
            $query->where('type', $type);
        }
        if (in_array($media, [MediaType::Image->value, MediaType::Video->value, MediaType::None->value], true)) {
            $query->where('media_type', $media);
        }
        if (in_array($trap, array_map(fn (TrapType $t) => $t->value, TrapType::cases()), true)) {
            $query->withTrap($trap);
        }
        if ($search !== '') {
            // Szukamy też w tłumaczeniu bieżącego języka, inaczej użytkownik widzący
            // pytania po niemiecku wpisywałby niemieckie słowa i nie znajdował nic —
            // bo `text` trzyma wyłącznie polski oryginał.
            //
            // Celujemy w KONKRETNY klucz JSON (`translations->en->q`), a nie LIKE po całej
            // kolumnie: surowy LIKE przeszukiwałby też pozostałe języki i klucz `pjm`
            // (nazwy plików .wmv), dając trafienia nie do wytłumaczenia dla użytkownika.
            //
            // Składnia `->` Laravela, a nie własny JSON_EXTRACT: produkcja to MariaDB,
            // a testy SQLite — te bazy mają inne funkcje JSON (m.in. SQLite nie zna
            // JSON_UNQUOTE). Gramatyka Laravela tłumaczy to na właściwy dialekt.
            $translationKey = config('locales.'.app()->getLocale().'.questions_key');

            $query->where(function ($q) use ($search, $translationKey) {
                $q->where('text', 'like', '%'.$search.'%')
                    ->orWhere('external_id', 'like', '%'.$search.'%');

                if ($translationKey) {
                    $q->orWhere("translations->{$translationKey}->q", 'like', '%'.$search.'%');
                }
            });
        }

        $questions = $query->orderBy('id')->paginate(24)->withQueryString();

        return view('questions.index', [
            'questions' => $questions,
            'categories' => $categories,
            'categoryId' => $categoryId,
            'type' => $type,
            'media' => $media,
            'trap' => $trap,
            'search' => $search,
        ]);
    }

    public function show(Question $question): View
    {
        $question->load(['answers', 'categories', 'similars.similarQuestion:id,text,type,points']);

        // Prosta nawigacja „poprzednie / następne" po aktywnych pytaniach.
        $prevId = Question::active()->where('id', '<', $question->id)->max('id');
        $nextId = Question::active()->where('id', '>', $question->id)->min('id');

        // Powrót zawsze do LISTY (nie do Referera), ale z zachowanymi filtrami.
        $listQuery = session('questions.list_query');
        $backUrl = route('questions.index').($listQuery ? '?'.$listQuery : '');

        // Media (zdjęcie/wideo) obsługuje komponent <x-question-media> przez metody modelu.
        return view('questions.show', [
            'question' => $question,
            'prevId' => $prevId,
            'nextId' => $nextId,
            'backUrl' => $backUrl,
        ]);
    }
}
