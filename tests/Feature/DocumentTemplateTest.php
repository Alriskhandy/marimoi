<?php

namespace Tests\Feature;

use App\Models\DocumentTemplate;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentTemplateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Migrasi menyisipkan template "Standar MARIMOI"; tiap test mulai dari tabel kosong.
        DocumentTemplate::query()->delete();
        Storage::fake('public');
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function userWith(array $permissions, string $slug = 'admin-bappeda'): User
    {
        $role = Role::firstOrCreate(['slug' => $slug], ['name' => ucfirst($slug), 'description' => null]);
        foreach ($permissions as $name) {
            $role->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'web']));
        }

        return User::factory()->create(['role_id' => $role->id]);
    }

    private function manager(): User
    {
        return $this->userWith(['document-templates.view', 'document-templates.manage']);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Kop Resmi Bappeda',
            'description' => 'Untuk dokumen resmi',
            'header_enabled' => '1',
            'header_line1' => 'PEMERINTAH PROVINSI MALUKU UTARA',
            'header_line2' => 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
            'header_line3' => 'Sofifi',
            'accent_color' => '#0a84ff',
            'orientation' => 'landscape',
            'footer_text' => 'Sumber data: MARIMOI',
            'for_map' => '1',
            'for_analysis' => '1',
            'is_active' => '1',
            'sort_order' => '1',
        ], $overrides);
    }

    public function test_index_requires_view_permission(): void
    {
        $this->actingAs($this->userWith([], 'user'))
            ->get(route('document-templates.index'))
            ->assertForbidden();

        DocumentTemplate::factory()->create(['name' => 'Template Uji']);

        $this->actingAs($this->userWith(['document-templates.view'], 'admin-opd'))
            ->get(route('document-templates.index'))
            ->assertOk()
            ->assertSee('Template Uji')
            ->assertDontSee(route('document-templates.create'));
    }

    public function test_index_shows_orientation_and_filters_by_jenis(): void
    {
        DocumentTemplate::factory()->create(['name' => 'Kop Lanskap']);
        DocumentTemplate::factory()->portrait()->create(['name' => 'Kop Potret']);
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('document-templates.index'))
            ->assertOk()
            ->assertSee('Jenis')
            ->assertSee('Kop Lanskap')
            ->assertSee('Kop Potret')
            ->assertSee('Potret')
            ->assertSee('Lanskap');

        $this->actingAs($manager)->get(route('document-templates.index', ['jenis' => 'portrait']))
            ->assertOk()
            ->assertSee('Kop Potret')
            ->assertDontSee('Kop Lanskap');

        // Nilai jenis tak dikenal diabaikan (tampil semua).
        $this->actingAs($manager)->get(route('document-templates.index', ['jenis' => 'miring']))
            ->assertOk()
            ->assertSee('Kop Lanskap')
            ->assertSee('Kop Potret');
    }

    public function test_viewer_without_manage_permission_cannot_change_templates(): void
    {
        $template = DocumentTemplate::factory()->create();
        $viewer = $this->userWith(['document-templates.view'], 'admin-opd');

        $this->actingAs($viewer)->post(route('document-templates.store'), $this->payload())->assertForbidden();
        $this->actingAs($viewer)->put(route('document-templates.update', $template), $this->payload())->assertForbidden();
        $this->actingAs($viewer)->delete(route('document-templates.destroy', $template))->assertForbidden();
        $this->assertDatabaseCount('document_templates', 1);
    }

    public function test_manager_can_create_template_with_logos(): void
    {
        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload([
                'logo_left' => UploadedFile::fake()->image('pemprov.png', 200, 200),
                'logo_right' => UploadedFile::fake()->image('marimoi.png', 200, 200),
            ]))
            ->assertRedirect(route('document-templates.index'));

        $template = DocumentTemplate::sole();
        $this->assertSame('Kop Resmi Bappeda', $template->name);
        $this->assertSame('#0a84ff', $template->accent_color);
        $this->assertTrue($template->for_map && $template->for_analysis && $template->is_active);
        Storage::disk('public')->assertExists($template->header_logo_left);
        Storage::disk('public')->assertExists($template->header_logo_right);
        // Template pertama otomatis menjadi bawaan.
        $this->assertTrue($template->is_default);
    }

    public function test_layout_is_saved_and_normalized_to_the_page(): void
    {
        $this->actingAs($this->manager())->post(route('document-templates.store'), $this->payload([
            'orientation' => 'portrait',
            'layout' => json_encode([
                'map' => ['enabled' => false, 'x' => 10, 'y' => 5, 'w' => 80, 'h' => 60],
                'legend' => ['enabled' => true, 'x' => 95, 'y' => 70, 'w' => 30, 'h' => 20],
                'inset' => ['enabled' => false, 'x' => 0, 'y' => 0, 'w' => 20, 'h' => 20],
                'unknown' => ['enabled' => true, 'x' => 0, 'y' => 0, 'w' => 10, 'h' => 10],
            ]),
        ]))->assertSessionHasNoErrors();

        $layout = DocumentTemplate::sole()->resolvedLayout();

        $this->assertSame('portrait', DocumentTemplate::sole()->orientation);
        // Peta selalu aktif; posisi lain dipertahankan.
        $this->assertTrue($layout['map']['enabled']);
        $this->assertEquals(['x' => 10, 'y' => 5, 'w' => 80, 'h' => 60], array_intersect_key($layout['map'], array_flip(['x', 'y', 'w', 'h'])));
        // Kotak yang melewati tepi kanan digeser masuk halaman.
        $this->assertEquals(70, $layout['legend']['x']);
        $this->assertFalse($layout['inset']['enabled']);
        // Elemen tak dikenal dibuang; elemen yang tak dikirim memakai posisi bawaan potret.
        $this->assertArrayNotHasKey('unknown', $layout);
        $this->assertEquals(DocumentTemplate::defaultLayout('portrait')['north'], $layout['north']);
    }

    public function test_layout_and_orientation_are_validated(): void
    {
        $this->actingAs($this->manager())->post(route('document-templates.store'), $this->payload([
            'orientation' => 'miring',
            'layout' => json_encode(['map' => ['enabled' => true, 'x' => 0, 'y' => 0, 'w' => 1, 'h' => 50]]),
        ]))->assertSessionHasErrors(['orientation', 'layout.map.w']);
    }

    public function test_kop_lines_one_and_two_are_uppercase_and_line_three_keeps_line_breaks(): void
    {
        $this->actingAs($this->manager())->post(route('document-templates.store'), $this->payload([
            'header_line1' => 'Pemerintah Provinsi Maluku Utara',
            'header_line2' => 'Badan Perencanaan Pembangunan Daerah',
            'header_line3' => "Jl. Raya Lintas Halmahera, Sofifi\r\nTelp. (0921) 123456 · bappeda@malutprov.go.id",
            'footer_text' => "Sumber data: MARIMOI\nPeta ini bukan referensi batas resmi.",
        ]))->assertSessionHasNoErrors();

        $header = DocumentTemplate::sole()->toPublicArray()['header'];

        $this->assertSame('PEMERINTAH PROVINSI MALUKU UTARA', $header['line1']);
        $this->assertSame('BADAN PERENCANAAN PEMBANGUNAN DAERAH', $header['line2']);
        // Baris 3 sesuai isian (tidak dikapitalkan), Enter dipertahankan sebagai \n.
        $this->assertSame("Jl. Raya Lintas Halmahera, Sofifi\nTelp. (0921) 123456 · bappeda@malutprov.go.id", $header['line3']);
        $this->assertSame("Sumber data: MARIMOI\nPeta ini bukan referensi batas resmi.", DocumentTemplate::sole()->toPublicArray()['footer']['text']);
    }

    public function test_footer_right_side_is_not_configurable(): void
    {
        $this->assertFalse(Schema::hasColumn('document_templates', 'footer_note'));
        $this->assertFalse(Schema::hasColumn('document_templates', 'show_print_date'));

        $this->actingAs($this->manager())->get(route('document-templates.create'))
            ->assertOk()
            ->assertDontSee('name="footer_note"', false)
            ->assertSee('Sisi kanan footer diisi otomatis')
            ->assertSee('Arial 12pt, kapital')
            ->assertSee('Arial 14pt, tebal, kapital')
            ->assertSee('Arial 10pt, sesuai isian')
            ->assertSee('Arial 9pt');
    }

    public function test_unchecked_boxes_are_saved_as_false(): void
    {
        $this->actingAs($this->manager())->post(route('document-templates.store'), $this->payload([
            'header_enabled' => null,
            'for_analysis' => null,
        ]));

        $template = DocumentTemplate::sole();
        $this->assertFalse($template->header_enabled);
        $this->assertFalse($template->for_analysis);
        $this->assertTrue($template->for_map);
    }

    public function test_validation_rejects_invalid_input(): void
    {
        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload([
                'name' => '',
                'accent_color' => 'biru',
                'for_map' => null,
                'for_analysis' => null,
                'logo_left' => UploadedFile::fake()->create('logo.pdf', 10, 'application/pdf'),
            ]))
            ->assertSessionHasErrors(['name', 'accent_color', 'for_map', 'logo_left']);

        $this->actingAs($this->manager())
            ->post(route('document-templates.store'), $this->payload(['is_active' => null, 'is_default' => '1']))
            ->assertSessionHasErrors(['is_default']);

        $this->assertDatabaseCount('document_templates', 0);
    }

    public function test_only_one_template_is_default(): void
    {
        $old = DocumentTemplate::factory()->asDefault()->create();

        $this->actingAs($this->manager())->post(route('document-templates.store'), $this->payload(['is_default' => '1']));

        $this->assertFalse($old->fresh()->is_default);
        $this->assertTrue(DocumentTemplate::where('name', 'Kop Resmi Bappeda')->sole()->is_default);
        $this->assertSame(1, DocumentTemplate::where('is_default', true)->count());
    }

    public function test_update_replaces_and_removes_logos(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post(route('document-templates.store'), $this->payload([
            'logo_left' => UploadedFile::fake()->image('lama.png'),
            'logo_right' => UploadedFile::fake()->image('kanan.png'),
        ]));
        $template = DocumentTemplate::sole();
        $oldLeft = $template->header_logo_left;
        $oldRight = $template->header_logo_right;

        $this->actingAs($manager)
            ->put(route('document-templates.update', $template), $this->payload([
                'name' => 'Kop Baru',
                'logo_left' => UploadedFile::fake()->image('baru.png'),
                'remove_logo_right' => '1',
            ]))
            ->assertRedirect(route('document-templates.index'));

        $template->refresh();
        $this->assertSame('Kop Baru', $template->name);
        $this->assertNotSame($oldLeft, $template->header_logo_left);
        Storage::disk('public')->assertMissing($oldLeft);
        Storage::disk('public')->assertExists($template->header_logo_left);
        $this->assertNull($template->header_logo_right);
        Storage::disk('public')->assertMissing($oldRight);
    }

    public function test_deleting_default_template_promotes_another_and_removes_logos(): void
    {
        $manager = $this->manager();
        $this->actingAs($manager)->post(route('document-templates.store'), $this->payload([
            'is_default' => '1',
            'logo_left' => UploadedFile::fake()->image('logo.png'),
        ]));
        $default = DocumentTemplate::sole();
        $logo = $default->header_logo_left;
        $other = DocumentTemplate::factory()->create(['name' => 'Cadangan']);

        $this->actingAs($manager)->delete(route('document-templates.destroy', $default))->assertRedirect(route('document-templates.index'));

        $this->assertModelMissing($default);
        Storage::disk('public')->assertMissing($logo);
        $this->assertTrue($other->fresh()->is_default);
    }

    public function test_public_logo_route_serves_only_active_templates(): void
    {
        $this->actingAs($this->manager())->post(route('document-templates.store'), $this->payload([
            'logo_left' => UploadedFile::fake()->image('logo.png'),
        ]));
        auth()->logout();
        $template = DocumentTemplate::sole();

        $this->get(route('document-templates.logo', ['documentTemplate' => $template, 'slot' => 'left']))->assertOk();
        $this->get(route('document-templates.logo', ['documentTemplate' => $template, 'slot' => 'right']))->assertNotFound();

        $template->update(['is_active' => false]);
        $this->get(route('document-templates.logo', ['documentTemplate' => $template, 'slot' => 'left']))->assertNotFound();
    }

    public function test_map_page_exposes_only_active_templates_with_default_first(): void
    {
        DocumentTemplate::factory()->create(['name' => 'Kedua', 'sort_order' => 1, 'for_analysis' => false]);
        DocumentTemplate::factory()->asDefault()->create(['name' => 'Utama', 'sort_order' => 5]);
        DocumentTemplate::factory()->inactive()->create(['name' => 'Arsip Lama']);
        DocumentTemplate::factory()->withoutHeader()->create(['name' => 'Polos', 'sort_order' => 2]);

        $response = $this->get(route('tampil.interaktif'))->assertOk();
        $templates = $response->viewData('documentTemplates');

        $this->assertSame(['Utama', 'Kedua', 'Polos'], array_column($templates, 'name'));
        $this->assertTrue($templates[0]['isDefault']);
        $this->assertFalse($templates[1]['forAnalysis']);
        $this->assertNull($templates[2]['header']);
        $this->assertSame('landscape', $templates[0]['orientation']);
        $this->assertSame(['map', 'legend', 'inset', 'scale', 'north'], array_keys($templates[0]['layout']));
        $this->assertSame('PEMERINTAH PROVINSI MALUKU UTARA', $templates[0]['header']['line1']);
        $this->assertSame('BADAN PERENCANAAN PEMBANGUNAN DAERAH', $templates[0]['header']['line2']);
        $this->assertSame(['text'], array_keys($templates[0]['footer']));
        $response->assertSee('window.MARIMOI_DOCUMENT_TEMPLATES', false)
            ->assertDontSee('Arsip Lama');
    }

    public function test_migration_seeds_a_default_template_and_permission_catalog(): void
    {
        $this->assertContains('document-templates.view', Permission::catalogNames());
        $this->assertContains('document-templates.manage', Permission::catalogNames());

        // setUp() mengosongkan tabel; jalankan ulang migrasi khusus untuk memastikan isi bawaannya.
        $this->artisan('migrate:refresh', ['--path' => 'database/migrations/2026_10_10_045147_create_document_templates_table.php'])->assertSuccessful();

        $this->assertSame('Standar MARIMOI', DocumentTemplate::sole()->name);
        $this->assertTrue(DocumentTemplate::sole()->is_default);
    }

    public function test_create_and_edit_forms_render_with_preview(): void
    {
        $manager = $this->manager();

        $this->actingAs($manager)->get(route('document-templates.create'))
            ->assertOk()
            ->assertSee('Tambah Template Dokumen')
            ->assertSee('name="logo_left"', false)
            ->assertSee('id="previewHeader"', false);

        $template = DocumentTemplate::factory()->create(['name' => 'Kop Lama', 'header_line2' => 'BAPPEDA MALUT']);

        $this->actingAs($manager)->get(route('document-templates.edit', $template))
            ->assertOk()
            ->assertSee('Ubah Template Dokumen')
            ->assertSee('value="Kop Lama"', false)
            ->assertSee('BAPPEDA MALUT');
    }

    public function test_sidebar_shows_menu_for_permitted_users(): void
    {
        $this->actingAs($this->userWith(['dashboard.view', 'document-templates.view']))
            ->get(route('document-templates.index'))
            ->assertSee('Template Dokumen')
            ->assertSee('href="'.route('document-templates.index').'"', false);
    }
}
