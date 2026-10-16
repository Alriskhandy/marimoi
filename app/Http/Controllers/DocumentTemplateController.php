<?php

namespace App\Http\Controllers;

use App\Http\Requests\DocumentTemplateRequest;
use App\Models\DocumentTemplate;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Kelola template dokumen untuk hasil Unduh Peta & cetak Analisis Peta di Peta Interaktif.
 */
class DocumentTemplateController extends Controller
{
    public function index(Request $request): View
    {
        $jenis = array_key_exists($request->query('jenis'), DocumentTemplate::ORIENTATIONS) ? $request->query('jenis') : null;
        $templates = DocumentTemplate::query()
            ->when($jenis, fn ($query) => $query->where('orientation', $jenis))
            ->ordered()
            ->get();
        $counts = DocumentTemplate::query()->selectRaw('orientation, count(*) as total')->groupBy('orientation')->pluck('total', 'orientation');

        return view('backend.pages.document-templates.index', compact('templates', 'jenis', 'counts'));
    }

    public function create(): View
    {
        return view('backend.pages.document-templates.form', [
            'template' => new DocumentTemplate([
                'header_enabled' => true,
                'accent_color' => '#1d3557',
                'orientation' => 'landscape',
                'for_map' => true,
                'for_analysis' => true,
                'is_active' => true,
                'header_line1' => 'PEMERINTAH PROVINSI MALUKU UTARA',
                'header_line2' => 'BADAN PERENCANAAN PEMBANGUNAN DAERAH',
                'footer_text' => 'Sumber data: MARIMOI — Bappeda Provinsi Maluku Utara',
            ]),
        ]);
    }

    public function store(DocumentTemplateRequest $request): RedirectResponse
    {
        $template = DocumentTemplate::create($this->attributes($request));
        $this->syncLogos($request, $template);
        $this->syncDefault($request, $template);

        return redirect()->route('document-templates.index')->with('success', "Template \"{$template->name}\" berhasil ditambahkan.");
    }

    public function edit(DocumentTemplate $documentTemplate): View
    {
        return view('backend.pages.document-templates.form', ['template' => $documentTemplate]);
    }

    public function update(DocumentTemplateRequest $request, DocumentTemplate $documentTemplate): RedirectResponse
    {
        $documentTemplate->update($this->attributes($request));
        $this->syncLogos($request, $documentTemplate);
        $this->syncDefault($request, $documentTemplate);

        return redirect()->route('document-templates.index')->with('success', "Template \"{$documentTemplate->name}\" berhasil diperbarui.");
    }

    public function destroy(DocumentTemplate $documentTemplate): RedirectResponse
    {
        $name = $documentTemplate->name;
        $wasDefault = $documentTemplate->is_default;
        $documentTemplate->delete();

        // Selalu sisakan satu template bawaan bila masih ada template aktif.
        if ($wasDefault) {
            DocumentTemplate::query()->active()->ordered()->first()?->makeDefault();
        }

        return redirect()->route('document-templates.index')->with('success', "Template \"{$name}\" berhasil dihapus.");
    }

    /**
     * Logo kop untuk tampilan publik (Peta Interaktif). Hanya template aktif; disajikan dari
     * disk lewat rute ini supaya satu origin dengan halaman dan tidak bergantung storage:link.
     */
    public function logo(DocumentTemplate $documentTemplate, string $slot): Response
    {
        $column = DocumentTemplate::LOGO_SLOTS[$slot] ?? null;
        $path = $column ? $documentTemplate->{$column} : null;

        abort_unless($documentTemplate->is_active && $path && Storage::disk('public')->exists($path), 404);

        return Storage::disk('public')->response($path, null, ['Cache-Control' => 'public, max-age=86400']);
    }

    /**
     * @return array<string, mixed>
     */
    private function attributes(DocumentTemplateRequest $request): array
    {
        $data = $request->safe()->except(['logo_left', 'logo_right', 'remove_logo_left', 'remove_logo_right', 'is_default']);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['layout'] = DocumentTemplate::normalizeLayout($data['layout'] ?? null, $data['orientation']);

        return $data;
    }

    private function syncLogos(DocumentTemplateRequest $request, DocumentTemplate $template): void
    {
        $changes = [];

        foreach (DocumentTemplate::LOGO_SLOTS as $slot => $column) {
            $replace = $request->hasFile("logo_{$slot}");

            if (($replace || $request->boolean("remove_logo_{$slot}")) && $template->{$column}) {
                Storage::disk('public')->delete($template->{$column});
                $changes[$column] = null;
            }
            if ($replace) {
                $changes[$column] = $request->file("logo_{$slot}")->store(DocumentTemplate::LOGO_DIRECTORY, 'public');
            }
        }

        if ($changes) {
            $template->update($changes);
        }
    }

    private function syncDefault(DocumentTemplateRequest $request, DocumentTemplate $template): void
    {
        if ($request->boolean('is_default')) {
            $template->makeDefault();

            return;
        }

        if ($template->is_default) {
            $template->forceFill(['is_default' => false])->save();
        }

        // Template aktif pertama menjadi bawaan bila belum ada bawaan sama sekali.
        if (! DocumentTemplate::query()->where('is_default', true)->exists()) {
            DocumentTemplate::query()->active()->ordered()->first()?->makeDefault();
        }
    }
}
