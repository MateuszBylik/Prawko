1. Silnik — serce aplikacji
1.1. Model wiedzy per pytanie
Dla każdego pytania w bazie i każdego użytkownika trzymasz p_correct — prawdopodobieństwo poprawnej odpowiedzi w warunkach egzaminacyjnych.
Dwa źródła:

Pytania widziane → FSRS (albo SM-2, ale FSRS jest lepszy i ma gotowe implementacje). Stan: stability, difficulty, last_review. p_correct = retrievability(t).
Pytania niewidziane (na starcie: 95% bazy) → prior z modelu Rascha. Każde pytanie ma parametr trudności b_q (estymowany z agregatu wszystkich użytkowników), każdy użytkownik parametr umiejętności θ_u. p = σ(θ_u − b_q).

To rozwiązuje zimny start uczciwie: po 40-pytaniowej diagnozie znasz θ_u i masz sensowne oszacowanie dla całej bazy, której użytkownik nie dotknął.
Kluczowy detal: „poprawnie, ale w 44 sekundy" przy limicie 35 s to błąd. Timer musi wchodzić do modelu, inaczej prognoza kłamie. Zapisuj time_to_answer przy każdej odpowiedzi i licz p_correct jako P(poprawnie ∧ w limicie).
1.2. Estymator — Monte Carlo na losowaniu
Egzamin losuje ze sztywnej struktury:
WarstwaLiczbaWagaSumapodstawowe10 / 6 / 43 / 2 / 146specjalistyczne6 / 4 / 23 / 2 / 128
Czyli 6 warstw, razem 74 pkt.
Algorytm: 10 000 iteracji. W każdej losujesz bez zwracania z każdej warstwy zgodnie z tabelą, dla każdego wylosowanego pytania rzucasz monetą o p_correct, sumujesz punkty. Wynik:

P(zdania) = odsetek symulacji z wynikiem ≥ 68
Prognozowany wynik = mediana + przedział (np. „69 pkt, 90% CI: 64–73")

To jest liczba, której nikt na rynku nie pokazuje, bo wymaga modelu — a wszyscy pokazują „85% poprawnych odpowiedzi", co przy progu 91,89% i wagach jest bezużyteczne, wręcz mylące.
Koszt: 320k losowań, w PHP ~50–100 ms. Licz w kolejce po każdej sesji, cache w Redisie. Wersję „na żywo" (pasek rosnący w trakcie sesji) możesz przeliczać w TS po stronie klienta na tych samych parametrach.
1.3. Kolejka ryzyka
Zamiast „przerób 3000 pytań" — kolejka posortowana po oczekiwanej stracie punktów:
strata(q) = (n_warstwy / N_warstwy) × waga(q) × (1 − p_correct(q))
Efekt uboczny, który jest właściwie sednem: pytania za 1 pkt z licznej warstwy schodzą na sam dół i użytkownik ich prawie nie widzi. Słusznie — one nie decydują o egzaminie. Cały czas idzie tam, gdzie tracisz punkty.

2. Warstwa treści — jak nie napisać 4000 wyjaśnień
To jest Twoje wąskie gardło i uważam, że postawione jest źle. Nie pisz wyjaśnień do pytań. Napisz reguły.

Cała baza kat. B redukuje się do ~120–180 atomów wiedzy („reguła": pierwszeństwo pojazdu szynowego, definicja skrzyżowania równorzędnego, 3,5 t / 750 kg, odstęp na autostradzie…).
Każde pytanie taguje się do 1–3 reguł.
„Wyjaśnienie" = reguła (napisana raz, ręcznie, dobrze, z grafiką) + jedno zdanie zastosowania („tu chodzi o pojazd szynowy, więc…").

Robota spada z 4000 tekstów do ~150 tekstów + 4000 jednozdaniowych mapowań, które generujesz modelem i akceptujesz hurtem w Filamencie.
Kolejność pisania: posortuj pytania po agregatowy_error_rate × waga i napisz reguły pokrywające top 400. Wystartuj z tym. Reszta bazy to pytania, które i tak wszyscy trafiają.
Pipeline: job na Horizonie generuje szkic mapowania → status szkic → kolejka recenzji w Filamencie (akceptuj / popraw / odrzuć) → zatwierdzone. Nigdy nie pokazuj szkic jako autorytetu. Przycisk „to nie pomogło" pod wyjaśnieniem wrzuca pytanie z powrotem do kolejki recenzji — masz darmowy sygnał jakości.

3. Tryby nauki
Diagnoza (onboarding, ~10 min). 40 pytań adaptacyjnych, stratyfikowanych. Wynik: pierwsza prognoza. To jest moment „aha" — użytkownik po 10 minutach wie, że ma 34% szans, a nie „87% poprawnych".
Sesja — kolejka ryzyka, zawsze z timerem egzaminacyjnym (20+15 s / 50 s). Bez trybu „bez czasu", bo on uczy złych nawyków.
Pojedynek bliźniaków — mój ulubiony i, moim zdaniem, najmocniejszy wyróżnik. W bazie są pary pytań różniące się jednym słowem, z odwrotną odpowiedzią. Wykryj je automatycznie: normalizacja tekstu → podobieństwo (trigramy albo embeddingi) → klaster o wysokim podobieństwie i różnej odpowiedzi = para myląca. Tryb pokazuje oba pytania pod rząd i wymusza rozróżnienie. Tu ludzie tracą egzamin i nikt tego nie trenuje.
Pułapki — taksonomia jako tagi na pytaniach: modalność (obowiązek/możliwość), negacja, kwantyfikator (zawsze/tylko/wyłącznie), zakres („w tej sytuacji" vs zasada ogólna), liczba. Filtruj sesję po typie pułapki. Bonus: podświetlaj słowo-klucz w feedbacku po błędzie, nie w treści pytania.
Egzamin próbny — pełne 32 pytania, 25 min, bez powrotów, film odtwarzany raz. Zero litości, bo inaczej nie kalibruje.
Plan do egzaminu — użytkownik wbija datę, aplikacja liczy wstecz z krzywej zapominania FSRS dzienną dawkę potrzebną do P(zdania) ≥ 95% w dniu X. Push/mail: „dziś 22 pytania, 14 minut".

4. Dashboard
Jedna wielka liczba: szansa zdania. Pod nią prognozowany wynik z przedziałem. Pod tym jeden przycisk: Ucz się.
Cała ta maszyneria — Rasch, FSRS, Monte Carlo — musi być niewidoczna. To jest ryzyko tego projektu: zrobić coś matematycznie eleganckiego, czego 18-latek nie chce, bo on chce klikać pytania. Złożoność w backendzie, jeden guzik na froncie. Zakładka „skąd ta liczba?" dla ciekawych, schowana.

5. Symulator skrzyżowań
Zostaje odłożony i to jest dobra decyzja. Uzasadnienie liczbowe: pierwszeństwo to najgęstszy klaster pytań za 3 pkt, ale pojedynki bliźniaków + reguły dają więcej punktów egzaminacyjnych na godzinę Twojej pracy niż silnik GSAP/SVG. Symulator to funkcja marketingowa (screenshot, który sprzedaje), nie funkcja ucząca — więc buduj go, gdy będziesz miał czym przyciągać ruch, a nie żeby mieć czym.
Kiedy wejdzie: podepnij go pod regułę, nie pod pytanie. Jedna reguła „pierwszeństwo na skrzyżowaniu równorzędnym" → jedna interaktywna scena, wielokrotnego użytku dla ~80 pytań.

6. MVP — co tnę
Zostaje: diagnoza, kolejka ryzyka, prognoza, egzamin próbny, 400 reguł, pary mylące.
Wypada z v1: symulator, gamifikacja (streaki, odznaki, ranking), tryb offline, kat. inne niż B, społeczność, wideo-kurs.
Pary mylące zostawiam w MVP mimo że to najwięcej roboty w wykrywaniu — bo to jedyna rzecz, której użytkownik nie dostanie za darmo z ministerialnej bazy, i jedyny powód, żeby wybrać Ciebie zamiast pierwszej lepszej appki z reklamami.