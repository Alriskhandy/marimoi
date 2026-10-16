<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Template dokumen untuk hasil unduhan publik (Unduh Peta & cetak Analisis Peta):
 * kop/header, footer, dan template yang bisa dipilih pengunjung.
 */
return new class extends Migration
{
    /**
     * Permission modul ini. Diberikan ke super-admin dan admin-bappeda (pengelola
     * identitas dokumen Bappeda); role lain diatur operator lewat Manajemen Role.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = ['document-templates.view', 'document-templates.manage'];

    public function up(): void
    {
        Schema::create('document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description')->nullable();

            // Kop / header
            $table->boolean('header_enabled')->default(true);
            $table->string('header_logo_left')->nullable();
            $table->string('header_logo_right')->nullable();
            $table->string('header_line1', 150)->nullable();
            $table->string('header_line2', 150)->nullable();
            $table->string('header_line3')->nullable();
            $table->string('accent_color', 7)->default('#1d3557');

            // Footer
            $table->string('footer_text')->nullable();
            $table->string('footer_note')->nullable();
            $table->boolean('show_print_date')->default(true);

            // Penggunaan & ketersediaan di tampilan publik
            $table->boolean('for_map')->default(true);
            $table->boolean('for_analysis')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('document_templates')->insert([
            'name' => 'Standar MARIMOI',
            'description' => 'Kop Bappeda Provinsi Maluku Utara untuk peta dan analisis.',
            'header_enabled' => true,
            'header_line1' => 'PEMERINTAH PROVINSI MALUKU UTARA',
            'header_line2' => 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
            'header_line3' => 'Jl. Raya Lintas Halmahera, Sofifi · bappeda.malutprov.go.id',
            'accent_color' => '#1d3557',
            'footer_text' => 'Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara',
            'for_map' => true,
            'for_analysis' => true,
            'is_active' => true,
            'is_default' => true,
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $roleIds = DB::table('roles')->whereIn('slug', ['super-admin', 'admin-bappeda'])->pluck('id');

        foreach (self::PERMISSIONS as $name) {
            $permissionId = DB::table('permissions')->where('name', $name)->value('id')
                ?? DB::table('permissions')->insertGetId([
                    'name' => $name,
                    'guard_name' => 'web',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            foreach ($roleIds as $roleId) {
                DB::table('role_has_permissions')->insertOrIgnore([
                    'permission_id' => $permissionId,
                    'role_id' => $roleId,
                ]);
            }
        }

        app()['cache']->forget('spatie.permission.cache');
    }

    public function down(): void
    {
        $ids = DB::table('permissions')->whereIn('name', self::PERMISSIONS)->pluck('id');
        DB::table('role_has_permissions')->whereIn('permission_id', $ids)->delete();
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app()['cache']->forget('spatie.permission.cache');

        Schema::dropIfExists('document_templates');
    }
};
