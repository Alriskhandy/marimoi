<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class LogController extends Controller
{
    /**
     * Log entries older than this from the tail of the file are ignored,
     * so a huge log doesn't have to be fully parsed/held in memory.
     */
    private const MAX_PARSED_ENTRIES = 5000;

    private const LEVELS = [
        'emergency',
        'alert',
        'critical',
        'error',
        'warning',
        'notice',
        'info',
        'debug',
    ];

    public function index(Request $request)
    {
        $files = $this->availableFiles();

        $selectedFile = $request->get('file');
        if (!$selectedFile || !$files->contains('filename', $selectedFile)) {
            $selectedFile = $files->first()['filename'] ?? null;
        }

        $entries = collect();
        $stats = ['total' => 0, 'error' => 0, 'warning' => 0, 'info' => 0, 'debug' => 0];
        $fileMeta = null;

        if ($selectedFile) {
            $path = $this->resolvePath($selectedFile);
            $fileMeta = $files->firstWhere('filename', $selectedFile);
            $entries = $this->parseLogFile($path);

            foreach ($entries as $entry) {
                $stats['total']++;
                $stats[$this->levelBucket($entry['level'])]++;
            }
        }

        $level = $request->get('level');
        $search = trim((string) $request->get('search', ''));

        $filtered = $entries
            ->when($level, fn($rows) => $rows->filter(fn($e) => strtolower($e['level']) === strtolower($level)))
            ->when($search !== '', function ($rows) use ($search) {
                return $rows->filter(function ($e) use ($search) {
                    return stripos($e['message'], $search) !== false
                        || stripos($e['context'], $search) !== false;
                });
            })
            ->values();

        $perPage = (int) $request->get('per_page', 25);
        $perPage = in_array($perPage, [10, 25, 50, 100, 200]) ? $perPage : 25;
        $page = (int) $request->get('page', 1);

        $paginated = new LengthAwarePaginator(
            $filtered->forPage($page, $perPage)->values(),
            $filtered->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('backend.pages.logs.index', [
            'files' => $files,
            'selectedFile' => $selectedFile,
            'fileMeta' => $fileMeta,
            'logs' => $paginated,
            'stats' => $stats,
            'levels' => self::LEVELS,
            'level' => $level,
            'search' => $search,
            'retentionDays' => (int) config('logging.channels.daily.days', 14),
        ]);
    }

    public function download(Request $request)
    {
        $filename = $this->validatedFilename($request->get('file'));
        $path = $this->resolvePath($filename);

        abort_unless(file_exists($path), 404, 'File log tidak ditemukan.');

        return response()->download($path, $filename);
    }

    /**
     * Empty a log file's contents while keeping the file itself (so the app
     * can keep writing to it without needing to recreate it).
     */
    public function clear(Request $request)
    {
        $filename = $this->validatedFilename($request->get('file'));
        $path = $this->resolvePath($filename);

        abort_unless(file_exists($path), 404, 'File log tidak ditemukan.');

        file_put_contents($path, '');

        Log::info("Log file cleared by admin: {$filename}", ['user_id' => auth()->id()]);

        return redirect()
            ->route('logs.index', ['file' => $filename])
            ->with('success', "Isi file log \"{$filename}\" berhasil dikosongkan.");
    }

    public function destroy(Request $request)
    {
        $filename = $this->validatedFilename($request->get('file'));
        $path = $this->resolvePath($filename);

        abort_unless(file_exists($path), 404, 'File log tidak ditemukan.');

        unlink($path);

        return redirect()
            ->route('logs.index')
            ->with('success', "File log \"{$filename}\" berhasil dihapus.");
    }

    /**
     * Delete every log file older than the configured daily retention,
     * mirroring what Laravel's daily driver already prunes automatically —
     * offered here as a manual maintenance action.
     */
    public function pruneOld(Request $request)
    {
        $days = (int) config('logging.channels.daily.days', 14);
        $cutoff = now()->subDays($days)->startOfDay();

        $deleted = 0;
        foreach ($this->availableFiles() as $file) {
            if ($file['modified']->lt($cutoff)) {
                @unlink($this->resolvePath($file['filename']));
                $deleted++;
            }
        }

        return redirect()
            ->route('logs.index')
            ->with('success', $deleted > 0
                ? "{$deleted} file log lama (lebih dari {$days} hari) berhasil dihapus."
                : 'Tidak ada file log lama yang perlu dihapus.');
    }

    /**
     * List every *.log file in storage/logs, newest first.
     */
    private function availableFiles()
    {
        $dir = storage_path('logs');

        if (!is_dir($dir)) {
            return collect();
        }

        return collect(glob($dir . DIRECTORY_SEPARATOR . '*.log'))
            ->map(function ($path) {
                $filename = basename($path);
                $modified = Carbon::createFromTimestamp(filemtime($path));

                // laravel-2026-07-16.log -> 16 Juli 2026, otherwise show the raw filename
                $label = $filename;
                if (preg_match('/^(?<prefix>[a-z]+)-(?<date>\d{4}-\d{2}-\d{2})\.log$/i', $filename, $m)) {
                    $label = ucfirst($m['prefix']) . ' — ' . Carbon::parse($m['date'])->translatedFormat('d F Y');
                }

                return [
                    'filename' => $filename,
                    'label' => $label,
                    'size' => $this->humanFileSize(filesize($path)),
                    'modified' => $modified,
                ];
            })
            ->sortByDesc('modified')
            ->values();
    }

    /**
     * Split a log file into individual entries (a Laravel log line can span
     * multiple physical lines because of stack traces / context arrays) and
     * parse the leading "[date] channel.LEVEL: message" header of each.
     */
    private function parseLogFile(?string $path)
    {
        if (!$path || !file_exists($path)) {
            return collect();
        }

        $content = file_get_contents($path);
        if ($content === false || trim($content) === '') {
            return collect();
        }

        $chunks = preg_split('/(?=^\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\])/m', $content, -1, PREG_SPLIT_NO_EMPTY);

        // Only look at the most recent entries so huge log files stay fast to parse.
        $chunks = array_slice($chunks, -self::MAX_PARSED_ENTRIES);

        $entries = collect();

        foreach ($chunks as $chunk) {
            $lines = explode("\n", rtrim($chunk));
            $firstLine = array_shift($lines);

            if (!preg_match(
                '/^\[(?<dt>\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2})\]\s+(?<chanlevel>[^:]+):\s*(?<msg>.*)$/s',
                $firstLine,
                $m
            )) {
                continue;
            }

            $channel = $m['chanlevel'];
            $level = 'info';
            if (preg_match('/^(?<chan>.*)\.(?<lvl>[A-Z]+)$/', trim($m['chanlevel']), $cm)) {
                $channel = $cm['chan'];
                $level = strtolower($cm['lvl']);
            }

            [$date, $time] = explode(' ', $m['dt']);

            $entries->push([
                'datetime' => $m['dt'],
                'date' => $date,
                'time' => $time,
                'channel' => $channel,
                'level' => $level,
                'message' => trim($m['msg']),
                'context' => trim(implode("\n", $lines)),
            ]);
        }

        // Newest first.
        return $entries->reverse()->values();
    }

    private function levelBucket(string $level): string
    {
        return match ($level) {
            'emergency', 'alert', 'critical', 'error' => 'error',
            'warning' => 'warning',
            'notice', 'info' => 'info',
            default => 'debug',
        };
    }

    private function validatedFilename(?string $filename): string
    {
        abort_if(!$filename || !preg_match('/^[A-Za-z0-9_\-]+\.log$/', $filename), 400, 'Nama file log tidak valid.');

        return $filename;
    }

    private function resolvePath(string $filename): string
    {
        $dir = realpath(storage_path('logs'));
        $path = $dir . DIRECTORY_SEPARATOR . $filename;

        abort_if(!$dir || dirname($path) !== $dir, 400, 'Path file log tidak valid.');

        return $path;
    }

    private function humanFileSize(int $bytes, int $decimals = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = $bytes > 0 ? floor(log($bytes, 1024)) : 0;
        $factor = min($factor, count($units) - 1);

        return sprintf("%.{$decimals}f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}
