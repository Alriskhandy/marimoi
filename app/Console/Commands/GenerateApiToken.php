<?php

namespace App\Console\Commands;

use App\Models\ApiToken;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class GenerateApiToken extends Command
{
    protected $signature = 'api:token:generate
                            {name : Nama client/aplikasi yang akan menggunakan token}
                            {--expires= : Tanggal kedaluwarsa (format: Y-m-d), kosongkan untuk tidak kedaluwarsa}';

    protected $description = 'Generate API token baru untuk akses API v1';

    public function handle(): int
    {
        $name      = $this->argument('name');
        $expiresAt = $this->option('expires')
            ? now()->parse($this->option('expires'))->endOfDay()
            : null;

        // Generate raw token: prefix + 32 byte hex
        $raw    = 'marimoi_' . bin2hex(random_bytes(32));
        $hashed = hash('sha256', $raw);

        ApiToken::create([
            'name'       => $name,
            'token'      => $hashed,
            'expires_at' => $expiresAt,
        ]);

        $this->newLine();
        $this->info("✓ Token berhasil dibuat untuk: <comment>{$name}</comment>");

        if ($expiresAt) {
            $this->line("  Kedaluwarsa  : <comment>{$expiresAt->toDateString()}</comment>");
        } else {
            $this->line("  Kedaluwarsa  : <comment>Tidak kedaluwarsa</comment>");
        }

        $this->newLine();
        $this->warn("  Raw Token (simpan sekarang — tidak bisa ditampilkan lagi):");
        $this->line("  <fg=yellow;options=bold>{$raw}</>");
        $this->newLine();
        $this->line("  Cara pakai:");
        $this->line("  <fg=cyan>Authorization: Bearer {$raw}</>");
        $this->newLine();

        return self::SUCCESS;
    }
}
