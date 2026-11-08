<?php

namespace App\Http\Controllers;

use App\Models\Attribution;
use App\Models\Beacon;
use App\Models\Event;
use App\Services\AnalysisService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        protected AnalysisService $analysisService
    ) {}

    /**
     * Display the dashboard with overview stats.
     */
    public function index(Request $request): Response
    {
        $userId = $request->user()->id;

        // Get beacon counts
        $beaconStats = [
            'total' => Beacon::where('user_id', $userId)->count(),
            'campaigns' => Beacon::where('user_id', $userId)->campaigns()->count(),
            'locations' => Beacon::where('user_id', $userId)->locations()->count(),
        ];

        // Get events in last 30 days
        $eventStats = Event::query()
            ->whereHas('beacon', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->where('heard_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(heard_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get total events
        $totalEvents = Event::query()
            ->whereHas('beacon', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        // Get attributions
        $attributionStats = Attribution::query()
            ->whereHas('campaignBeacon', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->limit(30)
            ->get();

        $totalAttributions = Attribution::query()
            ->whereHas('campaignBeacon', function ($query) use ($userId) {
                $query->where('user_id', $userId);
            })
            ->count();

        // Get recent beacons
        $recentBeacons = Beacon::query()
            ->where('user_id', $userId)
            ->withCount('events')
            ->latest()
            ->limit(5)
            ->get();

        // Get AI analysis
        $aiAnalysis = $this->analysisService->generateAttributionAnalysis($userId);

        return Inertia::render('dashboard', [
            'beaconStats' => $beaconStats,
            'eventStats' => $eventStats,
            'totalEvents' => $totalEvents,
            'attributionStats' => $attributionStats,
            'totalAttributions' => $totalAttributions,
            'recentBeacons' => $recentBeacons,
            'aiAnalysis' => $aiAnalysis,
        ]);
    }
}
