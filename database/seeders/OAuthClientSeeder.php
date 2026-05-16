<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;

class OAuthClientSeeder extends Seeder
{
    public function run(): void
    {
        $repo = app(ClientRepository::class);

        // Passport 12: create() dengan confidential=true (default) = client credentials grant
        $public = $repo->create(null, 'Marimoi Public Client', '');
        $admin  = $repo->create(null, 'Marimoi Admin Client', '');

        $this->command->newLine();
        $this->command->warn('╔══════════════════════════════════════════════════════════════╗');
        $this->command->warn('║  SIMPAN CREDENTIALS INI — TIDAK BISA DILIHAT LAGI!           ║');
        $this->command->warn('╚══════════════════════════════════════════════════════════════╝');
        $this->command->table(
            ['Client', 'ID', 'Secret'],
            [
                ['Public (3 hari)', $public->id, $public->plainSecret],
                ['Admin  (7 hari)', $admin->id,  $admin->plainSecret],
            ]
        );
        $this->command->warn('Simpan ke .env (dev) atau secrets manager (production).');
        $this->command->newLine();
    }
}
