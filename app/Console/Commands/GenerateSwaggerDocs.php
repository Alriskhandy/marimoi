<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateSwaggerDocs extends Command
{
    protected $signature   = 'swagger:generate {doc=default : Nama dokumentasi}';
    protected $description = 'Generate Swagger API documentation dengan suppress PHP user warnings';

    public function handle(): int
    {
        // swagger-php 6.x emits E_USER_WARNING via trigger_error() selama validasi.
        // Laravel mengkonversi ini ke ErrorException — kita suppress sementara.
        $prev = error_reporting(error_reporting() & ~E_USER_WARNING & ~E_USER_NOTICE);

        try {
            $this->call('l5-swagger:generate', ['documentation' => $this->argument('doc')]);
            $this->info('Swagger docs generated successfully.');
            return self::SUCCESS;
        } finally {
            error_reporting($prev);
        }
    }
}
