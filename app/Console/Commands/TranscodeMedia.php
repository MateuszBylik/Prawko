<?php

namespace App\Console\Commands;

use App\Models\Question;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Process;

/**
 * Konwertuje materiały wideo z bazy (WMV) na web-owe MP4 (H.264/AAC, faststart),
 * żeby odtwarzały się w przeglądarce. Idempotentna: pomija już przetworzone pliki.
 * Wynik ląduje w storage/app/import/media/mp4/{nazwa}.mp4 i jest serwowany przez
 * MediaController (patrz trasa media.show).
 */
class TranscodeMedia extends Command
{
    protected $signature = 'prawko:transcode-media
        {--limit=0 : Przetwórz tylko N pierwszych plików (0 = wszystkie)}
        {--force : Nadpisz istniejące pliki MP4}';

    protected $description = 'Konwertuje wideo pytań z WMV na MP4 (H.264) do odtwarzania w przeglądarce';

    /** Podkatalogi ze źródłowymi plikami. */
    private const SOURCE_DIRS = ['cz. 1', 'cz. 2'];

    public function handle(): int
    {
        if (! $this->ffmpegAvailable()) {
            $this->error('Nie znaleziono ffmpeg w PATH. Zainstaluj ffmpeg i spróbuj ponownie.');
            return self::FAILURE;
        }

        $base = storage_path('app/import/media');
        $outDir = $base.'/mp4';
        if (! is_dir($outDir) && ! mkdir($outDir, 0755, true) && ! is_dir($outDir)) {
            $this->error("Nie mogę utworzyć katalogu: {$outDir}");
            return self::FAILURE;
        }

        $paths = Question::query()
            ->where('media_type', 'video')
            ->whereNotNull('media_path')
            ->distinct()
            ->orderBy('media_path')
            ->pluck('media_path');

        $limit = (int) $this->option('limit');
        if ($limit > 0) {
            $paths = $paths->take($limit);
        }
        $force = (bool) $this->option('force');

        $total = $paths->count();
        $this->info("Do przetworzenia: {$total} plików wideo → {$outDir}");

        $ok = $done = $skipped = $missing = $failed = 0;
        $i = 0;

        foreach ($paths as $mediaPath) {
            $i++;
            $stem = pathinfo($mediaPath, PATHINFO_FILENAME);
            $target = $outDir.'/'.$stem.'.mp4';

            if (! $force && is_file($target) && filesize($target) > 0) {
                $skipped++;
                continue;
            }

            $source = $this->locateSource($base, $mediaPath);
            if ($source === null) {
                $missing++;
                $this->line(sprintf('[%d/%d] BRAK ŹRÓDŁA: %s', $i, $total, $mediaPath));
                continue;
            }

            $tmp = $target.'.tmp.mp4';
            $result = Process::timeout(600)->run([
                'ffmpeg', '-y', '-hide_banner', '-loglevel', 'error',
                '-i', $source,
                '-c:v', 'libx264', '-preset', 'veryfast', '-crf', '26',
                '-vf', "scale='min(1280,iw)':-2",
                '-c:a', 'aac', '-b:a', '96k',
                '-movflags', '+faststart', '-pix_fmt', 'yuv420p',
                $tmp,
            ]);

            if ($result->successful() && is_file($tmp) && filesize($tmp) > 0) {
                rename($tmp, $target);
                @chmod($target, 0644);
                $ok++;
                $done++;
            } else {
                @unlink($tmp);
                $failed++;
                $this->line(sprintf('[%d/%d] BŁĄD: %s — %s', $i, $total, $mediaPath,
                    trim(mb_substr($result->errorOutput(), 0, 200))));
            }

            if ($i % 25 === 0) {
                $this->line(sprintf('… postęp %d/%d (nowe: %d, pominięte: %d, błędy: %d)',
                    $i, $total, $done, $skipped, $failed));
            }
        }

        $this->newLine();
        $this->table(
            ['Nowe MP4', 'Pominięte', 'Brak źródła', 'Błędy', 'Razem'],
            [[$ok, $skipped, $missing, $failed, $total]],
        );

        return self::SUCCESS;
    }

    private function ffmpegAvailable(): bool
    {
        return Process::run(['ffmpeg', '-version'])->successful();
    }

    private function locateSource(string $base, string $mediaPath): ?string
    {
        $name = basename($mediaPath);
        foreach (self::SOURCE_DIRS as $dir) {
            $candidate = $base.'/'.$dir.'/'.$name;
            if (is_file($candidate)) {
                return $candidate;
            }
        }
        return null;
    }
}
