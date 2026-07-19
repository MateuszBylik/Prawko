# Braki plików multimedialnych w bazie pytań

Stan na **2026-07-16**. Generowane skryptem `sprawdz-media.php`, który odwzorowuje
logikę wyszukiwania plików z aplikacji (`Question::videoUrl()`, `MediaController`).

> **UWAGA — o numerach.** W tym raporcie są DWA różne numery na pytanie:
> **`Nr`** to `external_id` — numer, który aplikacja pokazuje na ekranie (`#352`) i po
> którym działa wyszukiwarka w `/pytania`. **`id`** to klucz w bazie i **to jego używa URL**
> `/pytania/{id}`. Wejście na `/pytania/14004` da **404**, bo 14004 to `external_id`,
> a `id` sięgają tylko ~3705. W tabelach poniżej są oba plus gotowy link.

## Wynik: realny problem to 2 pytania, nie 99

- Sprawdzono **3100** pytań z materiałem; **3001** ma plik na miejscu.
- Brakuje plików w **99** pytaniach — ale **97 z nich to pytania W TRAKCIE WERYFIKACJI**
  (`source = review`, `is_active = 0`), czyli **jeszcze nieopublikowane**.
- **Realnie dotyczy to 2 aktywnych pytań.**

### Skąd biorą się pytania bez plików

Oficjalny `baza_pytan.xlsx` ma **dwa arkusze**, a importer (`prawko:import-questions`)
czyta oba:

| Arkusz | `source` | Pytań | `is_active` | Zakres numerów |
|---|---|---|---|---|
| „katalog" | `catalog` | 3525 | ✅ tak | 99–14064 |
| „W trakcie weryfikacji" | `review` | 180 | ❌ nie | **13834–14015** |

W arkuszu „katalog" jest **luka w numeracji: 13787 → 14059** (brak 272 numerów) — i to
właśnie w tę lukę wpadają pytania z drugiego arkusza. Dlatego szukając numeru w okolicach
13900 **tylko w „katalogu", nie znajdziesz go** — mimo że jest w pliku i w bazie.

To wyjaśnia brak plików: paczki multimediów obejmują **wyłącznie opublikowany katalog**.
Pytanie czekające na weryfikację nie ma jeszcze materiału i **to jest stan normalny, a nie
usterka**. Wszystkie 97 braków pochodzi z arkusza `review`.

Te pytania **nie pojawiają się nigdzie w aplikacji**: przeglądarka `/pytania` i `ExamService`
filtrują przez `->active()`. Nie trafiają do egzaminów, sesji nauki ani wyszukiwarki.
Gdy Ministerstwo je opublikuje, przyjdą z materiałami w kolejnej paczce.

---

## ⚠️ Do zdobycia: 2 pliki (pytania AKTYWNE)

To jedyna część tego dokumentu, która wymaga działania.

| Nr (w aplikacji) | id (w URL) | Link | Plik | Pytanie |
|---|---|---|---|---|
| #11150 | 3032 | `/pytania/3032` | `DSC_0055.JPG` → potrzebny jako `DSC_0055.JPG` | Czy znak ten, wraz z tabliczką, zabrania zatrzymywania się przed nim po tej stronie drogi, po której się znajduje? |
| #13074 | 3244 | `/pytania/3244` | `5-9.2021.wmv` → potrzebny jako `mp4/5-9.2021.mp4` | Czy w tej sytuacji, nie widząc innych uczestników ruchu drogowego, jesteś zwolniony z obowiązku zachowania szczególnej ostrożności? |

Gdy zdobędziesz pliki: zdjęcia do `storage/app/import/media/cz. 1/`, filmy przekonwertuj
do `storage/app/import/media/mp4/` jako `{nazwa}.mp4`, potem uruchom skrypt (na końcu).

---

## Pytania w trakcie weryfikacji (97) — dla porządku

**Nie wymagają żadnego działania** — brak plików jest tu oczekiwany (patrz wyżej).
Lista wyłącznie na wypadek, gdyby te pytania trafiły kiedyś do katalogu bez materiałów;
wtedy brakowałoby **77 plików**.


### Filmy (16 pytań)

| Nr | id | Plik | Pytanie |
|---|---|---|---|
| #13889 | 3581 | `1806ztV.wmv` | Zamierzasz kontynuować jazdę drogą z pierwszeństwem przejazdu. Czy musisz włączyć lewy kierunkowskaz? |
| #13890 | 3582 | `1808ztV.wmv` | Zamierzasz skręcić w lewo na najbliższym skrzyżowaniu. Czy musisz włączyć lewy kierunkowskaz? |
| #13907 | 3599 | `1808ztV.wmv` | Zamierzasz kontynuować jazdę drogą z pierwszeństwem przejazdu. Czy musisz włączyć prawy kierunkowskaz? |
| #13908 | 3600 | `5-9.2021.wmv` | Czy w tej sytuacji, zbliżając się do skrzyżowania, jesteś zwolniony z obowiązku zachowania szczególnej ostrożności? |
| #13913 | 3605 | `8-9.2021.wmv` | Czy w przedstawionej sytuacji należy zachować szczególną ostrożność? |
| #13914 | 3606 | `4-9.2021.wmv` | Czy przejeżdżając obok widocznego przystanku tramwajowego jesteś zwolniony z obowiązku zachowania szczególnej ostrożności? |
| #13930 | 3622 | `9-9.2021.wmv` | Czy podczas cofania, przy braku innych uczestników ruchu na tym odcinku drogi, możesz zrezygnować z zachowania szczególnej ostrożności? |
| #13933 | 3624 | `10-9.2021.wmv` | Czy w tej sytuacji możesz nie zachowywać szczególnej ostrożności? |
| #13934 | 3625 | `10-9.2021.wmv` | Czy w tej sytuacji powinieneś zmniejszyć prędkość i zachować szczególną ostrożność? |
| #13935 | 3626 | `11-9.2021.wmv` | Czy w tej sytuacji powinieneś zachować szczególną ostrożność i zmniejszyć prędkość? |
| #13938 | 3629 | `13-9.2021.wmv` | Czy w tej sytuacji powinieneś zachować ograniczone zaufanie wobec wyprzedzanego rowerzysty, który nie trzyma rąk na kierownicy? |
| #13940 | 3631 | `14-9.2021.wmv` | Czy kierując pojazdem po zmierzchu oraz przy znacznym zachmurzeniu powinieneś zachować szczególną ostrożność? |
| #13949 | 3640 | `W16 16.wmv` | Czy w tej sytuacji możesz kontynuować jazdę bez zmniejszenia prędkości? |
| #13960 | 3651 | `PW12-2025-10p.wmv` | Czy w takiej sytuacji jadąc odcinkiem jezdni jednokierunkowej dopuszczalne jest wyprzedzanie pojazdu z jego lewej strony? |
| #13997 | 3687 | `PW12b-2025-1.wmv` | Czy w tej sytuacji, w strefie zamieszkania powinieneś użyć sygnału dźwiękowego? |
| #14006 | 3696 | `PW12-2025-33.wmv` | Czy zabronione jest wymijanie pojazdu na przejeździe kolejowym? |

### Zdjęcia (81 pytań)

| Nr | id | Plik | Pytanie |
|---|---|---|---|
| #13839 | 3531 | `zdj. nieb.prędk.pyt.6a.jpg` | Czy w tych warunkach powinieneś kierować pojazdem z wykorzystaniem limitu prędkości 50 km/h w obszarze zabudowanym? |
| #13840 | 3532 | `zmrok przejazd.jpg` | Czy kierujący pojazdem zbliżając się do przejazdu kolejowego w czasie zapadającego zmroku powinien maksymalnie ograniczyć prędkość a w razie potrzeby zatrzymać kierowany pojazd, aby zapewnić sobie możliwość pełnej obserwacji sytuacji po obydwu stronach przejazdu? |
| #13841 | 3533 | `zakręt z barierkami zdj..jpg` | Czy nadmierna prędkość przy wjeździe w taki zakręt, może spowodować utratę panowania nad pojazdem, nawet w przypadku suchej nawierzchni jezdni? |
| #13842 | 3534 | `zakręt z barierkami.jpg` | Czy nadmierna prędkość, przy wjeździe w widoczny zakręt, może spowodować utratę panowania nad pojazdem? |
| #13843 | 3535 | `zdj. nieb.prędk. pyt.22.jpg` | Czy kierujący pojazdem, jadąc drogą za tym znakiem drogowym, powinien mieć świadomość wydłużenia drogi hamowania? |
| #13844 | 3536 | `zdj. nieb.prędk. pyt.20.jpg` | Czy niezachowanie odpowiedniego odstępu podczas wyprzedzania rowerzysty może doprowadzić rowerzystę do utraty panowania nad swoim pojazdem oraz innych poważnych konsekwencji? |
| #13845 | 3537 | `zdj. nieb.prędk. pyt.21.jpg` | Czy nadmierna prędkość jest główną przyczyną utraty panowania nad pojazdem? |
| #13846 | 3538 | `zdj. nieb.prędk. pyt.17.jpg` | Czy jazda z prędkością większą niż dopuszczalna na danej drodze zwiększa prawdopodobieństwo spowodowania lub udziału w wypadku drogowym? |
| #13847 | 3539 | `zdj. nieb.prędk. pyt.17.jpg` | Czy przyczyną tego wypadku mogła być niedostosowana do warunków ruchu prędkość pojazdu? |
| #13848 | 3540 | `zdj. nieb.prędk.pyt.16.jpg` | Czy kierujący przy ustalaniu prędkości powinien uwzględnić stan techniczny pojazdu, który prowadzi? |
| #13849 | 3541 | `zdj. nieb.prędk.pyt.14.jpg` | Czy kierujący pojazdem widząc, że zbliża się do odcinka jezdni pokrytej częściowo mokrymi liśćmi powinien zmniejszyć prędkość tak, żeby nie doprowadzić do poślizgu kół? |
| #13851 | 3543 | `zdj. nieb.prędk. pyt.12.jpg` | Czy kierujący pojazdem, zbliżając się do widocznego przejazdu kolejowego, powinien jechać z prędkością zapewniającą możliwość zatrzymania pojazdu przed tym przejazdem? |
| #13852 | 3544 | `zdj. nieb.prędk. pyt.10.jpg` | Czy w przestawionej sytuacji kierujący pojazdem powinien liczyć się z koniecznością dodatkowego zmniejszenia prędkości? |
| #13853 | 3545 | `zdj. nieb.prędk.pyt.11.jpg` | Czy kierując pojazdem na drodze za tym znakiem, powinieneś uwzględnić możliwość wystąpienia oszronienia jezdni lub gołoledzi i w związku z tym zmniejszyć prędkość, żeby nie utracić panowania nad pojazdem? |
| #13854 | 3546 | `zdj.nieb.prędk. pyt.6.JPG` | Czy kierując pojazdem w czasie mgły, dla zachowania szczególnej ostrożności powinieneś dostosować prędkość do widoczności drogi i zachować odpowiedni odstęp od poprzedzającego pojazdu? |
| #13855 | 3547 | `zdj.nieb.prędk. pyt.5.jpg` | Czy kierujący pojazdem, przy doborze prędkości jazdy powinien brać pod uwagę natężenie ruchu? |
| #13856 | 3548 | `zdj. nieb. prędk. pyt.4.jpg` | Czy w takim terenie jazda z nadmierną prędkością może doprowadzić do utraty panowania nad pojazdem i uderzenia w przydrożne drzewo ? |
| #13857 | 3549 | `zdj.nieb. prędk. pyt.2.jpg` | Czy wjeżdżając na most po ustaniu opadów atmosferycznych, przy doborze prędkości, powinieneś wziąć pod uwagę, że nawierzchni drogi może być mokra dłużej niż na innych odcinkach drogi? |
| #13858 | 3550 | `Wyprzedzanie2.jpg` | Czy na tej dwukierunkowej jezdni na obszarze zabudowanym, dopuszczalne jest wyprzedzanie z prawej strony? |
| #13860 | 3552 | `Wyprzedzanie3.jpg` | Czy w przedstawionej sytuacji, na obszarze zabudowanym, masz prawo wyprzedzić jadący pojazd z prawej strony? |
| #13861 | 3553 | `Wyprzedzanie3.jpg` | Czy w przedstawionej sytuacji, na obszarze zabudowanym, wolno Ci wyprzedzić pojazd z jego prawej strony? |
| #13864 | 3556 | `Wyprzedzanie4.jpg` | Czy w przedstawionej sytuacji wolno Ci wyprzedzić pojazdy jadące sąsiednim pasem ruchu? |
| #13865 | 3557 | `Wyprzedzanie4.jpg` | Czy w przedstawionej sytuacji, jadąc prawym pasem ruchu zabronione jest wyprzedzanie pojazdów jadących sąsiednim pasem ruchu? |
| #13867 | 3559 | `Wyprzedzanie5.jpg` | Czy w przedstawionej sytuacji masz prawo wyprzedzić pojazd jadący prawym pasem ruchu? |
| #13868 | 3560 | `Wyprzedzanie5.jpg` | Czy w przedstawionej sytuacji obowiązuje Cię zakaz wyprzedzania pojazdu jadącego prawym pasem ruchu? |
| #13869 | 3561 | `Wyprzedzanie7.jpg` | Czy w przedstawionej sytuacji chcąc zrealizować tzw. zasadę suwaka możesz wyprzedzić jadące obok samochody? |
| #13871 | 3563 | `Wyprzedzanie17.jpg` | Czy w przedstawionej sytuacji masz obowiązek podczas wyprzedzania rowerzysty zachować odstęp  bezpieczny i nie mniejszy niż 1 m? |
| #13872 | 3564 | `Wyprzedzanie17.jpg` | Czy w przedstawionej sytuacji obowiązuje przepis dotyczący zachowania minimalnego odstępu 1 m przy wyprzedzaniu rowerzysty? |
| #13873 | 3565 | `Wyprzedzanie17.jpg` | Czy w przedstawionej sytuacji mimo wyznaczenia pasa ruchu dla rowerów, przy wyprzedzaniu rowerzysty występuje obowiązek zachowania szczególnej ostrożności? |
| #13874 | 3566 | `Wyprzedzanie16.jpg` | Czy za widocznym znakiem zabronione jest wyprzedzanie rowerzysty? |
| #13875 | 3567 | `Wyprzedzanie16.jpg` | Czy na tak oznakowanej jezdni dopuszczalne jest wyprzedzanie motoroweru? |
| #13878 | 3570 | `Wyprzedzanie19.jpg` | Czy kierując samochodem ciężarowym o dopuszczalnej masie całkowitej przekraczającej 3,5 t wolno Ci za tym znakiem wyprzedzić motocykl jednośladowy? |
| #13888 | 3580 | `Wyprzedzanie13.jpg` | Czy w przedstawionej sytuacji możesz wyprzedzić po lewej stronie wolno jadący środkowym pasem ruchu pojazd? |
| #13894 | 3586 | `B3 260 -265.jpg` | Czy kierując poza obszarem zabudowanym w tunelu o długości przekraczającej 500 m samochodem o dopuszczalnej masie całkowitej do 3,5 t masz obowiązek zachować odstęp od pojazdu poprzedzającego nie mniejszy niż 50 m? |
| #13895 | 3587 | `B3 260 -265.jpg` | Czy kierując autobusem poza obszarem zabudowanym w tunelu o długości przekraczającej 500 m masz obowiązek zachować odstęp od pojazdu poprzedzającego nie mniejszy niż 50 m? |
| #13896 | 3588 | `B3 260 -265.jpg` | Czy kierując samochodem osobowym z przyczepą lekką poza obszarem zabudowanym w tunelu o długości przekraczającej 500 m możesz zachować odstęp od pojazdu poprzedzającego mniejszy niż 80 m, jednak nie mniejszy niż 50 m? |
| #13897 | 3589 | `B3 260 -265.jpg` | Czy kierując autobusem z przyczepą lekką poza obszarem zabudowanym w tunelu o długości przekraczającej 500 m możesz zachować odstęp od pojazdu poprzedzającego mniejszy niż 80 m, jednak nie mniejszy niż 50 m? |
| #13898 | 3590 | `B3 260 -265.jpg` | Czy kierując samochodem osobowym z przyczepą lekką poza obszarem zabudowanym w tunelu o długości przekraczającej 500 m masz obowiązek zachować odstęp od pojazdu poprzedzającego nie mniejszy niż 80 m? |
| #13899 | 3591 | `B3 260 -265.jpg` | Czy kierując autobusem z przyczepą lekką poza obszarem zabudowanym w tunelu o długości przekraczającej 500 m masz obowiązek zachować odstęp od pojazdu poprzedzającego nie mniejszy niż 80 m? |
| #13900 | 3592 | `B3 266.jpg` | Czy podczas kierowania samochodem osobowym na autostradzie z maksymalną dozwoloną prędkością masz obowiązek utrzymać odstęp za pojazdem jadącym na tym samym pasie ruchu wynoszący nie mniej niż 70 m? |
| #13901 | 3593 | `B3 267.jpg` | Czy podczas kierowania samochodem osobowym z przyczepą na drodze ekspresowej dwujezdniowej z maksymalną dozwoloną prędkością masz obowiązek utrzymać odstęp za pojazdem na tym samym pasie ruchu wynoszący nie mniej niż 40 m? |
| #13922 | 3614 | `Zdjęcie13707.jpg` | Jeśli masz prędkość 40 km/h i dojeżdżasz do skrzyżowania mając sygnał czerwony, to zgodnie z zasadami techniki kierowania, postąpisz prawidłowo wciskając : |
| #13946 | 3637 | `GNH03jpg.jpg` | Czy zmieniając pas ruchu lub kierunek jazdy hulajnogą elektryczną, masz obowiązek zawczasu i wyraźnie sygnalizować zamiar wykonania tego manewru przez wyciągnięcie ręki w odpowiednią stronę? |
| #13947 | 3638 | `GNH04jpg.jpg` | Czy kierując hulajnogą elektryczną w czasie mgły lub innych warunków zmniejszonej przejrzystości powietrza, masz obowiązek używać świateł pozycyjnych pojazdu? |
| #13948 | 3639 | `GNH07.jpg.jpg` | Czy wolno Ci kierować urządzeniem transportu osobistego na obszarze zabudowanym z prędkością 30 km/h? |
| #13952 | 3643 | `W12 510.jpg` | Czy na autostradzie dozwolone jest wyprzedzanie przez kierującego zespołem pojazdów o długości powyżej 7 m, składającym się z samochodu osobowego i przyczepy lekkiej? |
| #13953 | 3644 | `W12 511.jpg` | Czy w pokazanej sytuacji jadąc autostradą  z maksymalną dozwoloną prędkością autobusem spełniającym dodatkowe warunki techniczne  możesz wyprzedzać? |
| #13954 | 3645 | `W12 512.jpg` | Czy w przedstawionej sytuacji jadąc autostradą samochodem ciężarowym o maksymalnej masie całkowitej przekraczającej 3,5 t możesz wyprzedzić zespół pojazdów poruszający się z nieznacznie mniejszą prędkością od Twojej ? |
| #13955 | 3646 | `W12 514 - 515.jpg` | Czy podczas wyprzedzania po zmianie pasa ruchu masz obowiązek wyłączyć kierunkowskaz? |
| #13956 | 3647 | `W12 514 - 515.jpg` | Czy podczas wyprzedzania po zmianie pasa ruchu, mając zamiar nadal wyprzedzać powinieneś mieć włączony kierunkowskaz? |
| #13959 | 3650 | `W 12 520a.jpg` | Czy kierujący samochodem ciężarowym o maksymalnej masie całkowitej większej niż 3,5 t poruszając się po autostradzie może wyprzedzić widoczny na zdjęciu pojazd, który wykonuje prace porządkowe i wysyła żółte sygnały błyskowe? |
| #13961 | 3652 | `W12 542.jpg` | Czy podczas wyprzedzania z prawej strony na drodze ekspresowej dwujezdniowej samochodem osobowym z przyczepą możesz przekroczyć prędkość 80 km/h? |
| #13963 | 3654 | `Wyprzedzanie1.jpg` | Czy w przedstawionej sytuacji, podejmując decyzję o wyprzedzaniu rowerzysty,  masz obowiązek wziąć pod uwagę konieczność zachowania odstępu bocznego co najmniej  1 m, nawet gdyby Twoim zdaniem odstęp mniejszy byłby bezpieczny ? |
| #13964 | 3655 | `DSC08330.JPG` | Czy w tej sytuacji musisz zachować szczególną ostrożność? |
| #13966 | 3657 | `DSC08330.JPG` | Czy w przedstawionej sytuacji nie obowiązuje Ciebie obowiązek zachowania szczególnej ostrożności? |
| #13967 | 3658 | `DSC08391.JPG` | Czy w przedstawionej sytuacji zbliżasz się do przejścia dla pieszych, na którym ciąży na Tobie obowiązek zwrócenia dodatkowej uwagi na dzieci? |
| #13968 | 3659 | `DSC08422.JPG` | Czy w przedstawionej sytuacji jesteś uprzedzony jedynie o przejściu dla pieszych? |
| #13971 | 3661 | `DSC08422.JPG` | Czy w przedstawionej sytuacji jesteś uprzedzony o występowaniu obok siebie przejścia dla pieszych i przejazdu dla rowerów? |
| #13972 | 3662 | `DSC08861.JPG` | Czy w przedstawionej sytuacji masz obowiązek zatrzymać się zawsze przed przejściem dla pieszych? |
| #13973 | 3663 | `DSC08930.JPG` | Czy w miejscu tak oznaczonym masz obowiązek zawsze zatrzymać się przed widoczną linią zatrzymania? |
| #13974 | 3664 | `DSC08993.JPG` | Czy w tej sytuacji, jeśli pomimo znacznego zmniejszenia przez Ciebie prędkości dziecko nie wchodzi na przejście powinieneś zatrzymać pojazd, którym kierujesz? |
| #13977 | 3667 | `!RS_Parking zastrzeżony.jpg` | Czy wolno Ci zaparkować na tak oznakowanym stanowisku postojowym, jeżeli przewozisz pojazdem osobę niepełnosprawną legitymującą się kartą parkingową? |
| #13978 | 3668 | `!RS_Parking zastrzeżony.jpg` | Czy obowiązuje Cię zakaz postoju na tak oznakowanym stanowisku, jeżeli przewozisz pojazdem osobę niepełnosprawną legitymującą się kartą parkingową? |
| #13979 | 3669 | `GNI04.jpg.jpeg` | Czy kierując pojazdem konstrukcyjnie przeznaczonym do przewozu osób niepełnosprawnych, masz obowiązek włączyć światła awaryjne podczas wsiadania lub wysiadania osoby niepełnosprawnej ? |
| #13980 | 3670 | `GNI05.jpg.jpg` | Czy zachowując szczególną ostrożność, masz prawo nie stosować się do zakazu wyrażonego widocznym znakiem podczas przewozu osoby niepełnosprawnej legitymującej się kartą parkingową pojazdem oznaczonym taką kartą ? |
| #13981 | 3671 | `GNI07.jpg.jpg` | Czy zachowując szczególną ostrożność, możesz nie stosować się do zakazów wyrażonych widocznymi znakami podczas przewozu osoby niepełnosprawnej legitymującej się kartą parkingową pojazdem oznaczonym taką kartą? |
| #13982 | 3672 | `GNIP06.jpg.jpg` | Czy zachowując szczególną ostrożność, możesz nie stosować się do zakazów wyrażonych widocznymi znakami podczas przewozu osoby niepełnosprawnej legitymującej się kartą parkingową pojazdem oznaczonym taką kartą? |
| #13983 | 3673 | `GNH05.jpg.jpg` | Czy, zachowując szczególną ostrożność, wolno Ci przejechać przez jezdnię hulajnogą elektryczną po przejściu dla pieszych? |
| #13984 | 3674 | `GNH08.jpg.jpg` | Czy, zachowując szczególną ostrożność, wolno Ci przejechać przez jezdnię urządzeniem transportu osobistego po przejściu dla pieszych? |
| #13996 | 3686 | `Kartaparkingowa.jpg` | Czy przewożąc osobę niepełnosprawną legitymującą się kartą parkingową możesz nie stosować się do niektórych znaków drogowych dotyczących zakazu ruchu? |
| #13998 | 3688 | `Zdjęcie0034.jpg` | Czy, przewożąc osobą niepełnosprawną legitymującą się kartą parkingową, masz prawo nie stosować się do widocznego znaku? |
| #13999 | 3689 | `Zdjęcie0036.jpg` | Czy przewożąc osobę niepełnosprawną legitymującą się kartą parkingową wolno Ci na tym skrzyżowaniu skręcić w drogę znajdującą się po lewej stronie? |
| #14000 | 3690 | `Zdjęcie0037.jpg` | Czy, przewożąc osobą niepełnosprawną legitymującą się kartą parkingową, masz prawo zatrzymać się na widocznym przystanku? |
| #14001 | 3691 | `Zdjęcie0038.jpg` | Czy przewożąc osobę niepełnosprawną legitymującą się kartą parkingową wolno Ci wjechać na drogę oznaczoną tym znakiem? |
| #14003 | 3693 | `DSC08422.JPG` | Czy ten znak informacyjny odnosi się również do rowerzystów? |
| #14004 | 3694 | `MW 18(153).jpg` | Czy w tej sytuacji, po upewnieniu się, że nie nadjeżdża pociąg, możesz przejechać przez torowisko bez zatrzymania? |
| #14005 | 3695 | `PW12-2025-5.jpg` | Czy w tej sytuacji zbliżasz się do przejazdu kolejowego jednotorowego? |
| #14011 | 3701 | `DSC_0055.JPG` | Czy osoba niepełnosprawna kierująca samochodem osobowym, legitymująca się kartą parkingową, może nie stosować się do widocznego znaku zakazu? |
| #14012 | 3702 | `image 008GN.jpg` | Czy przewożąc samochodem osobowym każdego pasażera o ograniczonej sprawności ruchowej, jesteś zwolniony z obowiązku stosowania się do widocznego znaku zakazu? |
| #14013 | 3703 | `image 013GN.jpg` | Czy przewożąc osobę niepełnosprawną legitymującą się kartą parkingową możesz, pod warunkiem zachowania szczególnej ostrożności, nie stosować się do przedstawionego znaku zakazu? |
| #14014 | 3704 | `zdj. nieb.prędk.pyt.6a.jpg` | Czy w tych warunkach powinieneś kierować pojazdem z wykorzystaniem limitu prędkości 50 km/h w obszarze zabudowanym? |

---

## Powiązana usterka (naprawiona 2026-07-16)

Osobno od braków plików: `MediaController` deklarował `Cache-Control: private`, ale
`response()->file()` tworzy `BinaryFileResponse` z domyślnym `$public = true`, które
wywołuje `setPublic()` i kasuje `private`. Pliki szły więc jako **publiczne**, Cloudflare
je cache'ował — łącznie z 1-bajtową odpowiedzią `206` na sondę zakresu. Zatruty wpis
powodował, że przeglądarka dostawała 1 bajt zamiast zdjęcia i pokazywała
„Zdjęcie niedostępne w bazie źródłowej" **mimo obecnego pliku** (tak wyglądało `/pytania/7`).
Naprawione przez jawne `setPrivate()`. Gdyby objaw wrócił: wyczyść cache Cloudflare
i zrób twarde odświeżenie (Ctrl+Shift+R) — stara, zepsuta odpowiedź mogła zostać
zapisana w przeglądarce na 7 dni.

## Jak sprawdzić ponownie

```bash
runuser -u www-data -- php sprawdz-media.php
```
Wypisuje podsumowanie (z podziałem na aktywne/nieaktywne) i zapisuje pełne dane
do `braki-mediow.json`.
