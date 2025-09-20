<?php
// app/Http/Controllers/SurveyController.php

namespace App\Http\Controllers;

use App\Http\Requests\GeneralSurveyRequest;
use App\Models\Survey;
use App\Models\Publication;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SurveyController extends Controller
{
    // Menampilkan form survey umum
    public function showGeneralSurveyForm()
    {
        return view('surveys.general-form');
    }

    // Proses survey umum
    public function submitGeneralSurvey(GeneralSurveyRequest $request)
    {
        try {
            Survey::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'organization' => $request->organization,
                'position' => $request->position,
                'survey_type' => 'general',
                'rating' => $request->rating,
                'feedback' => $request->feedback,
                'suggestions' => $request->suggestions,
                'additional_data' => $request->additional_data,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return redirect()->route('survey.thank-you')
                ->with('success', 'Terima kasih atas feedback Anda!');
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => 'Terjadi kesalahan saat menyimpan survey. Silakan coba lagi.']);
        }
    }

    // Halaman terima kasih setelah survey
    public function thankYou()
    {
        return view('surveys.thank-you');
    }

    // Admin: Daftar semua survey
    public function index(Request $request)
    {
        $query = Survey::with(['publication', 'publicationDownload']);

        // Filter berdasarkan tipe survey
        if ($request->has('type') && in_array($request->type, ['general', 'download'])) {
            $query->where('survey_type', $request->type);
        }

        // Filter berdasarkan rating
        if ($request->has('rating') && $request->rating) {
            $query->where('rating', $request->rating);
        }

        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        // Filter berdasarkan pencarian
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('feedback', 'like', "%{$search}%")
                    ->orWhere('suggestions', 'like', "%{$search}%");
            });
        }

        $surveys = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.surveys.index', compact('surveys'));
    }

    // Admin: Detail survey
    public function show(Survey $survey)
    {
        $survey->load(['publication', 'publicationDownload']);
        return view('admin.surveys.show', compact('survey'));
    }

    // Admin: Analytics dashboard
    public function analytics()
    {
        // Total surveys
        $totalSurveys = Survey::count();
        $generalSurveys = Survey::general()->count();
        $downloadSurveys = Survey::download()->count();

        // Average rating
        $averageRating = Survey::avg('rating');
        $generalAvgRating = Survey::general()->avg('rating');
        $downloadAvgRating = Survey::download()->avg('rating');

        // Rating distribution
        $ratingDistribution = Survey::selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->orderBy('rating')
            ->get();

        // Rating distribution by type
        $ratingByType = Survey::selectRaw('survey_type, rating, COUNT(*) as count')
            ->groupBy('survey_type', 'rating')
            ->orderBy('survey_type')
            ->orderBy('rating')
            ->get();

        // Top rated publications
        $topRatedPublications = Publication::withCount('surveys')
            ->withAvg('surveys', 'rating')
            ->having('surveys_count', '>', 0)
            ->orderBy('surveys_avg_rating', 'desc')
            ->limit(10)
            ->get();

        // Survey berdasarkan tipe
        $surveysByType = Survey::selectRaw('survey_type, COUNT(*) as count')
            ->groupBy('survey_type')
            ->get();

        // Survey terbaru
        $recentSurveys = Survey::with(['publication'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Survey per bulan (6 bulan terakhir)
        $monthlyStats = Survey::selectRaw('
                DATE_FORMAT(created_at, "%Y-%m") as month,
                COUNT(*) as total,
                AVG(rating) as avg_rating,
                survey_type
            ')
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month', 'survey_type')
            ->orderBy('month')
            ->get();

        // Feedback terbaru yang ada isinya
        $recentFeedbacks = Survey::whereNotNull('feedback')
            ->where('feedback', '!=', '')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.surveys.analytics', compact(
            'totalSurveys',
            'generalSurveys',
            'downloadSurveys',
            'averageRating',
            'generalAvgRating',
            'downloadAvgRating',
            'ratingDistribution',
            'ratingByType',
            'topRatedPublications',
            'surveysByType',
            'recentSurveys',
            'monthlyStats',
            'recentFeedbacks'
        ));
    }

    // Admin: Export survey data
    public function exportSurveys(Request $request)
    {
        $query = Survey::with(['publication', 'publicationDownload']);

        // Filter berdasarkan tipe survey
        if ($request->has('type') && in_array($request->type, ['general', 'download'])) {
            $query->where('survey_type', $request->type);
        }

        // Filter berdasarkan tanggal
        if ($request->has('date_from') && $request->date_from) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to') && $request->date_to) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $surveys = $query->orderBy('created_at', 'desc')->get();

        // Return sebagai CSV
        $filename = 'surveys_export_' . now()->format('Y-m-d_H-i-s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ];

        $callback = function () use ($surveys) {
            $file = fopen('php://output', 'w');

            // Header CSV
            fputcsv($file, [
                'ID',
                'Nama',
                'Email',
                'Telepon',
                'Organisasi',
                'Posisi',
                'Tipe Survey',
                'Rating',
                'Feedback',
                'Saran',
                'Publikasi',
                'IP Address',
                'Tanggal'
            ]);

            // Data
            foreach ($surveys as $survey) {
                fputcsv($file, [
                    $survey->id,
                    $survey->name,
                    $survey->email,
                    $survey->phone,
                    $survey->organization,
                    $survey->position,
                    $survey->survey_type,
                    $survey->rating,
                    $survey->feedback,
                    $survey->suggestions,
                    $survey->publication ? $survey->publication->title : '-',
                    $survey->ip_address,
                    $survey->created_at->format('Y-m-d H:i:s'),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // Admin: Hapus survey
    public function destroy(Survey $survey)
    {
        $survey->delete();

        return redirect()->route('admin.surveys.index')
            ->with('success', 'Survey berhasil dihapus.');
    }

    // Admin: Bulk delete surveys
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'survey_ids' => 'required|array',
            'survey_ids.*' => 'exists:surveys,id'
        ]);

        Survey::whereIn('id', $request->survey_ids)->delete();

        return redirect()->route('admin.surveys.index')
            ->with('success', count($request->survey_ids) . ' survey berhasil dihapus.');
    }
}
