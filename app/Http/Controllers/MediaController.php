<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Serwuje materiały (zdjęcia/wideo) z katalogu importu poza `public/`.
 * W bazie `questions.media_path` to sama nazwa pliku; pliki leżą w jednym
 * z podkatalogów `storage/app/import/media`. Ścieżkę zawężamy do `basename`,
 * żeby uniemożliwić wyjście poza katalog (path traversal).
 */
class MediaController extends Controller
{
    /**
     * Podkatalogi, w których leżą pliki: `mp4` = wideo przekonwertowane na web
     * (H.264), potem oryginalne paczki ministerialne (zdjęcia + źródłowe WMV).
     */
    private const SUBDIRS = ['mp4', 'cz. 1', 'cz. 2'];

    public function show(Request $request, string $name): BinaryFileResponse
    {
        $name = basename($name);
        abort_if($name === '' || str_starts_with($name, '.'), 404);

        $base = storage_path('app/import/media');

        foreach (self::SUBDIRS as $sub) {
            $path = $base.DIRECTORY_SEPARATOR.$sub.DIRECTORY_SEPARATOR.$name;
            if (is_file($path)) {
                $response = response()->file($path);

                // UWAGA: samo podanie nagłówka 'Cache-Control: private' NIE działa —
                // response()->file() tworzy BinaryFileResponse z domyślnym $public=true,
                // które wywołuje setPublic() i kasuje 'private'. Trzeba setPrivate().
                //
                // Skutek publicznego cache'owania (zdarzył się naprawdę, 2026-07-16):
                // Cloudflare cache'uje te pliki, w tym 1-bajtową odpowiedź 206 na sondę
                // zakresu (Range: bytes=0-0). Taki wpis zatruwa cache — kolejne żądania
                // dostają 1 bajt zamiast zdjęcia, nie docierają do serwera, a przeglądarka
                // pokazuje „Zdjęcie niedostępne w bazie źródłowej" mimo obecnego pliku.
                $response->setPrivate();
                $response->setMaxAge(604800);

                return $response;
            }
        }

        abort(404);
    }
}
