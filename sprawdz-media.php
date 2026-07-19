<?php
/**
 * Sprawdza, dla których pytań brakuje plików multimedialnych.
 *
 * Odwzorowuje logikę aplikacji: zdjęcie = nazwa z `questions.media_path`
 * szukana w mp4/, „cz. 1", „cz. 2"; film = `{nazwa}.mp4` w mp4/
 * (patrz Question::videoUrl() i MediaController::SUBDIRS).
 *
 * Uruchamiać jako www-data:
 *   runuser -u www-data -- php sprawdz-media.php
 *
 * Wynik: podsumowanie na ekran + pełne dane w braki-mediow.json.
 */
require __DIR__.'/vendor/autoload.php';
$app = require __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$base = storage_path('app/import/media');
$subdirs = ['mp4', 'cz. 1', 'cz. 2'];

// Indeks plikow (bez rozroznienia wielkosci liter) — szybciej niz tysiace is_file()
$have = [];
foreach ($subdirs as $sub) {
    foreach (scandir("$base/$sub") ?: [] as $f) {
        if ($f[0] === '.') continue;
        $have[mb_strtolower($f)] = "$sub/$f";
    }
}

// is_active jest KLUCZOWE: przeglądarka pytań i ExamService filtrują przez ->active(),
// więc brak pliku w pytaniu nieaktywnym (wycofanym z oficjalnej puli) nikogo nie dotyka.
$rows = App\Models\Question::query()
    ->whereIn('media_type', ['image', 'video'])
    ->orderBy('id')
    ->get(['id', 'external_id', 'type', 'points', 'media_type', 'media_path', 'text', 'is_active']);

$missing = [];
$ok = 0;

foreach ($rows as $r) {
    if (! $r->media_path) {
        $missing[] = ['q' => $r, 'want' => '(pusty media_path)'];
        continue;
    }
    // Odwzorowuje logike aplikacji: film -> {nazwa}.mp4 w mp4/, zdjecie -> nazwa w 3 katalogach
    $want = $r->media_type->value === 'video'
        ? mb_strtolower(pathinfo($r->media_path, PATHINFO_FILENAME).'.mp4')
        : mb_strtolower(basename($r->media_path));

    if (isset($have[$want])) { $ok++; continue; }
    $missing[] = ['q' => $r, 'want' => $want];
}

$aktywne = collect($missing)->filter(fn ($m) => (bool) $m['q']->is_active);

echo 'sprawdzono pytan z mediami: '.$rows->count().PHP_EOL;
echo 'pliki OK: '.$ok.PHP_EOL;
echo 'BRAKUJE lacznie: '.count($missing).PHP_EOL;
echo '  >>> w tym AKTYWNYCH (realny problem): '.$aktywne->count().PHP_EOL;
echo '  nieaktywnych (wycofane z puli, nie sa losowane): '.(count($missing) - $aktywne->count()).PHP_EOL;
$byType = collect($missing)->groupBy(fn ($m) => $m['q']->media_type->value)->map->count();
foreach ($byType as $t => $c) echo "  $t: $c".PHP_EOL;

$out = collect($missing)->map(fn ($m) => [
    'id' => $m['q']->id,                       // numer w bazie = ten w URL /pytania/{id}
    'nr' => $m['q']->external_id,              // numer POKAZYWANY w aplikacji (#…)
    'aktywne' => (bool) $m['q']->is_active,
    'url' => url('/pytania/'.$m['q']->id),
    'typ' => $m['q']->media_type->value,
    'plik_w_bazie' => $m['q']->media_path,
    'szukany_plik' => $m['want'],
    'pytanie' => $m['q']->text,
])->values();
file_put_contents(__DIR__.'/braki-mediow.json', $out->toJson(JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo 'pelne dane: '.__DIR__.'/braki-mediow.json'.PHP_EOL;
