<?php

namespace Tests\Feature;

use App\Models\Aspirasi;
use App\Models\KategoriAspirasi;
use App\Models\Opd;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Halaman admin Aspirasi & Kategori Aspirasi (tampilan mengikuti Daftar Layer & Data).
 */
class AspirasiAdminPagesTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $slug, array $permissions, ?int $opdId = null): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => $slug, 'description' => null]);
        foreach ($permissions as $permission) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id, 'opd_id' => $opdId]);
    }

    private function aspirasi(KategoriAspirasi $kategori, array $overrides = []): Aspirasi
    {
        return Aspirasi::create(array_merge([
            'kategori_aspirasi_id' => $kategori->id,
            'nama_pengirim' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'alamat' => 'Jl. Uji Coba No. 1',
            'jenis_aspirasi' => 'usulan',
            'judul_aspirasi' => 'Perbaikan Jalan Rusak',
            'isi_aspirasi' => 'Jalan di depan rumah saya rusak parah.',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_aspirasi_page_lists_every_aspirasi_not_only_the_first_fifteen(): void
    {
        $kategori = KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur']);
        foreach (range(1, 20) as $number) {
            $this->aspirasi($kategori, ['judul_aspirasi' => "Aspirasi Nomor {$number}"]);
        }

        $this->actingAs($this->userWithRole('super-admin', []))
            ->get(route('aspirasi.index'))
            ->assertOk()
            ->assertSee('Aspirasi Nomor 1')
            ->assertSee('Aspirasi Nomor 20')
            ->assertViewHas('aspirasi', fn ($aspirasi) => $aspirasi->count() === 20);
    }

    public function test_aspirasi_page_uses_layer_style_toolbar_and_stat_shortcuts(): void
    {
        $opd = Opd::create(['name' => 'Dinas Pekerjaan Umum', 'singkatan' => 'PUPR']);
        $kategori = KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur', 'opd_id' => $opd->id]);
        $this->aspirasi($kategori, ['status' => 'diproses']);
        $this->aspirasi($kategori, ['status' => 'selesai', 'jenis_aspirasi' => 'kritik & saran']);

        $this->actingAs($this->userWithRole('super-admin', []))
            ->get(route('aspirasi.index'))
            ->assertOk()
            ->assertSee('class="row g-3 align-items-end filter-toolbar mb-3"', false)
            ->assertSee('id="jenisFilter"', false)
            ->assertSee('id="resetFiltersBtn"', false)
            ->assertSee('data-status-shortcut="diproses"', false)
            ->assertSee('data-opd="'.$opd->id.'"', false)
            ->assertSee('data-jenis="kritik &amp; saran"', false)
            ->assertSee('PUPR')
            ->assertViewHas('stats', fn (array $stats) => $stats['diproses'] === 1 && $stats['selesai'] === 1);
    }

    public function test_admin_opd_sees_only_own_categories_and_no_opd_filter(): void
    {
        $opd = Opd::create(['name' => 'Dinas Kesehatan', 'singkatan' => 'DINKES']);
        $other = Opd::create(['name' => 'Dinas Pendidikan', 'singkatan' => 'DIKBUD']);
        $milik = KategoriAspirasi::create(['nama_kategori' => 'Kesehatan', 'opd_id' => $opd->id]);
        $lain = KategoriAspirasi::create(['nama_kategori' => 'Pendidikan', 'opd_id' => $other->id]);
        $this->aspirasi($milik, ['judul_aspirasi' => 'Puskesmas Kekurangan Obat']);
        $this->aspirasi($lain, ['judul_aspirasi' => 'Sekolah Rusak']);

        $this->actingAs($this->userWithRole('admin-opd', ['aspirasi.view'], $opd->id))
            ->get(route('aspirasi.index'))
            ->assertOk()
            ->assertSee('Puskesmas Kekurangan Obat')
            ->assertDontSee('Sekolah Rusak')
            ->assertDontSee('id="opdFilter"', false)
            ->assertSee('<option value="'.$milik->id.'">Kesehatan</option>', false)
            ->assertDontSee('<option value="'.$lain->id.'">Pendidikan</option>', false);
    }

    public function test_kategori_page_shows_aspirasi_count_per_category(): void
    {
        $opd = Opd::create(['name' => 'Dinas Pekerjaan Umum', 'singkatan' => 'PUPR']);
        $infrastruktur = KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur', 'opd_id' => $opd->id]);
        KategoriAspirasi::create(['nama_kategori' => 'Kritik dan Saran']);
        $this->aspirasi($infrastruktur);
        $this->aspirasi($infrastruktur);

        $this->actingAs($this->userWithRole('super-admin', []))
            ->get(route('kategori-aspirasi.index'))
            ->assertOk()
            ->assertSee('id="kategoriTable"', false)
            ->assertSee('id="filterOPD"', false)
            ->assertSee('2 aspirasi')
            ->assertSee('0 aspirasi')
            ->assertSee('Umum')
            ->assertViewHas('stats', fn (array $stats) => $stats['total'] === 2
                && $stats['dengan_opd'] === 1
                && $stats['tanpa_opd'] === 1
                && $stats['total_aspirasi'] === 2);
    }

    public function test_aspirasi_modals_use_new_layout(): void
    {
        $this->actingAs($this->userWithRole('super-admin', []))
            ->get(route('aspirasi.index'))
            ->assertOk()
            ->assertSee('id="show_response_empty"', false)
            ->assertSee('id="status_ticket"', false)
            ->assertSee('pengirim menerima email', false)
            ->assertSee('<option value="diproses">Diproses</option>', false)
            ->assertDontSee('btn-close-white', false);
    }

    public function test_aspirasi_detail_json_includes_category_name_used_by_modal(): void
    {
        $opd = Opd::create(['name' => 'Dinas Pekerjaan Umum', 'singkatan' => 'PUPR']);
        $aspirasi = $this->aspirasi(KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur', 'opd_id' => $opd->id]));

        $this->actingAs($this->userWithRole('super-admin', []))
            ->getJson(route('aspirasi.show', $aspirasi), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertJsonPath('data.kategori_aspirasi.nama_kategori', 'Infrastruktur')
            ->assertJsonPath('data.kategori_aspirasi.opd.singkatan', 'PUPR');
    }

    public function test_kategori_modals_mark_opd_as_required_like_the_backend(): void
    {
        $this->actingAs($this->userWithRole('super-admin', []))
            ->get(route('kategori-aspirasi.index'))
            ->assertOk()
            ->assertSee('OPD Penanggung Jawab', false)
            ->assertSee('-- Pilih OPD --', false)
            ->assertDontSee('-- Kategori Umum --', false)
            ->assertSee('id="show_aspirasi_count"', false);

        $this->actingAs($this->userWithRole('super-admin', []))
            ->postJson(route('kategori-aspirasi.store'), ['nama_kategori' => 'Tanpa OPD'], ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['opd_id']);
    }

    public function test_kategori_detail_json_includes_aspirasi_count(): void
    {
        $kategori = KategoriAspirasi::create(['nama_kategori' => 'Infrastruktur']);
        $this->aspirasi($kategori);
        $this->aspirasi($kategori);

        $this->actingAs($this->userWithRole('super-admin', []))
            ->getJson(route('kategori-aspirasi.show', $kategori->id))
            ->assertOk()
            ->assertJsonPath('data.aspirasi_count', 2);
    }
}
