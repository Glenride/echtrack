<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use App\Models\Attribution;
use App\Models\Beacon;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BeaconController extends Controller
{
    /**
     * Display a listing of beacons.
     */
    public function index(Request $request): Response
    {
        $beacons = Beacon::query()
            ->where('user_id', $request->user()->id)
            ->withCount('events')
            ->with(['events' => function ($query) {
                $query->latest('heard_at')->limit(1);
            }])
            ->latest()
            ->paginate(10);

        return Inertia::render('beacons/index', [
            'beacons' => $beacons,
        ]);
    }

    /**
     * Show the form for creating a new beacon.
     */
    public function create(): Response
    {
        return Inertia::render('beacons/create');
    }

    /**
     * Store a newly created beacon.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|in:campaign,location',
            'description' => 'nullable|string|max:1000',
            'audio_file' => 'nullable|file|mimes:mp3,wav|max:51200', // 50MB max
        ]);

        $beacon = new Beacon([
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'user_id' => $request->user()->id,
        ]);

        // Handle audio file upload
        if ($request->hasFile('audio_file')) {
            $originalPath = $request->file('audio_file')->store('beacons/original', 'public');
            $beacon->original_audio_path = $originalPath;

            // For MVP, just copy the file as "watermarked" (real FSK encoding would happen here)
            $watermarkedPath = str_replace('/original/', '/watermarked/', $originalPath);
            Storage::disk('public')->copy($originalPath, $watermarkedPath);
            $beacon->watermarked_audio_path = $watermarkedPath;
        }

        $beacon->save();

        return redirect()->route('beacons.index')
            ->with('success', 'Beacon created successfully!');
    }

    /**
     * Display the specified beacon.
     */
    public function show(Request $request, Beacon $beacon): Response
    {
        // Ensure user owns this beacon
        if ($beacon->user_id !== $request->user()->id) {
            abort(403);
        }

        $beacon->loadCount('events');

        // Get recent events
        $recentEvents = $beacon->events()
            ->latest('heard_at')
            ->limit(50)
            ->get();

        // Get event stats for chart
        $eventStats = Event::query()
            ->where('beacon_id', $beacon->id)
            ->where('heard_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(heard_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Get attributions if this is a campaign beacon
        $attributions = [];
        if ($beacon->type === 'campaign') {
            $attributions = Attribution::query()
                ->where('campaign_beacon_id', $beacon->id)
                ->with('locationBeacon')
                ->latest()
                ->limit(20)
                ->get();
        }

        return Inertia::render('beacons/show', [
            'beacon' => $beacon,
            'recentEvents' => $recentEvents,
            'eventStats' => $eventStats,
            'attributions' => $attributions,
        ]);
    }

    /**
     * Update the specified beacon.
     */
    public function update(Request $request, Beacon $beacon)
    {
        if ($beacon->user_id !== $request->user()->id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'is_active' => 'boolean',
        ]);

        $beacon->update($validated);

        return redirect()->route('beacons.show', $beacon)
            ->with('success', 'Beacon updated successfully!');
    }

    /**
     * Remove the specified beacon.
     */
    public function destroy(Request $request, Beacon $beacon)
    {
        if ($beacon->user_id !== $request->user()->id) {
            abort(403);
        }

        // Delete audio files
        if ($beacon->original_audio_path) {
            Storage::disk('public')->delete($beacon->original_audio_path);
        }
        if ($beacon->watermarked_audio_path) {
            Storage::disk('public')->delete($beacon->watermarked_audio_path);
        }

        $beacon->delete();

        return redirect()->route('beacons.index')
            ->with('success', 'Beacon deleted successfully!');
    }

    /**
     * Download the watermarked audio file.
     */
    public function downloadWatermarked(Request $request, Beacon $beacon)
    {
        if ($beacon->user_id !== $request->user()->id) {
            abort(403);
        }

        if (!$beacon->watermarked_audio_path) {
            abort(404, 'No watermarked audio file available');
        }

        return Storage::disk('public')->download(
            $beacon->watermarked_audio_path,
            "watermarked_{$beacon->beacon_id}.mp3"
        );
    }
}
