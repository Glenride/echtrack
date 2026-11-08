<?php

namespace App\Services;

use App\Models\Attribution;
use App\Models\Beacon;
use App\Models\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;

class AnalysisService
{
    protected string $apiKey;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key', '');
        $this->enabled = !empty($this->apiKey);
    }

    /**
     * Generate an AI analysis of attribution data.
     */
    public function generateAttributionAnalysis(int $userId): array
    {
        // Check cache first (cache for 1 hour)
        $cacheKey = "analysis_attribution_{$userId}";
        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        // Gather stats
        $stats = $this->gatherStats($userId);

        // Generate analysis
        if ($this->enabled) {
            $analysis = $this->callOpenAI($stats);
        } else {
            $analysis = $this->generateMockAnalysis($stats);
        }

        // Cache the result
        $result = [
            'analysis' => $analysis,
            'generated_at' => now()->toISOString(),
            'is_mock' => !$this->enabled,
            'stats' => $stats,
        ];

        Cache::put($cacheKey, $result, 3600); // 1 hour

        return $result;
    }

    /**
     * Gather statistics for analysis.
     */
    protected function gatherStats(int $userId): array
    {
        $beaconStats = [
            'total' => Beacon::where('user_id', $userId)->count(),
            'campaigns' => Beacon::where('user_id', $userId)->campaigns()->count(),
            'locations' => Beacon::where('user_id', $userId)->locations()->count(),
        ];

        $totalEvents = Event::whereHas('beacon', fn($q) => $q->where('user_id', $userId))->count();
        $totalAttributions = Attribution::whereHas('campaignBeacon', fn($q) => $q->where('user_id', $userId))->count();

        // Events in last 7 days
        $recentEvents = Event::whereHas('beacon', fn($q) => $q->where('user_id', $userId))
            ->where('heard_at', '>=', now()->subDays(7))
            ->count();

        // Average time to conversion
        $avgConversionHours = Attribution::whereHas('campaignBeacon', fn($q) => $q->where('user_id', $userId))
            ->avg('time_to_conversion_hours') ?? 0;

        // Top performing campaigns
        $topCampaigns = Beacon::where('user_id', $userId)
            ->campaigns()
            ->withCount('campaignAttributions')
            ->orderBy('campaign_attributions_count', 'desc')
            ->limit(3)
            ->get()
            ->map(fn($b) => ['name' => $b->name, 'conversions' => $b->campaign_attributions_count]);

        return [
            'beacons' => $beaconStats,
            'total_events' => $totalEvents,
            'recent_events' => $recentEvents,
            'total_attributions' => $totalAttributions,
            'conversion_rate' => $totalEvents > 0 ? round(($totalAttributions / $totalEvents) * 100, 2) : 0,
            'avg_conversion_hours' => round($avgConversionHours, 1),
            'top_campaigns' => $topCampaigns->toArray(),
        ];
    }

    /**
     * Call OpenAI API for analysis.
     */
    protected function callOpenAI(array $stats): string
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->apiKey}",
                'Content-Type' => 'application/json',
            ])
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => 'gpt-4o-mini',
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are an analytics expert for an ultrasonic attribution platform. Provide brief, actionable insights based on the data. Keep responses under 150 words. Focus on conversion performance and recommendations.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Analyze this attribution data and provide insights:\n" . json_encode($stats, JSON_PRETTY_PRINT),
                    ],
                ],
                'max_tokens' => 300,
            ]);

            if ($response->successful()) {
                return $response->json('choices.0.message.content') ?? $this->generateMockAnalysis($stats);
            }

            return $this->generateMockAnalysis($stats);
        } catch (\Exception $e) {
            return $this->generateMockAnalysis($stats);
        }
    }

    /**
     * Generate mock analysis when OpenAI is not available.
     */
    protected function generateMockAnalysis(array $stats): string
    {
        if ($stats['total_events'] === 0) {
            return "📊 **Getting Started**\n\nNo event data yet. Deploy your beacons to start tracking:\n\n1. Create campaign beacons for your ads\n2. Create location beacons for your stores\n3. Integrate the SDK into partner apps\n4. Watch the conversions roll in!";
        }

        $conversionRate = $stats['conversion_rate'];
        $avgHours = $stats['avg_conversion_hours'];

        $insights = "📊 **Attribution Analysis**\n\n";

        if ($conversionRate > 5) {
            $insights .= "✅ Strong conversion rate of {$conversionRate}% - your campaigns are effectively driving foot traffic.\n\n";
        } elseif ($conversionRate > 1) {
            $insights .= "📈 Conversion rate at {$conversionRate}% shows promise. Consider A/B testing ad creatives to improve.\n\n";
        } else {
            $insights .= "⚠️ Low conversion rate ({$conversionRate}%). Review campaign targeting and ensure location beacons are properly deployed.\n\n";
        }

        if ($avgHours > 0) {
            if ($avgHours < 24) {
                $insights .= "⚡ Fast conversion time ({$avgHours}h avg) indicates high-intent audiences responding quickly to ads.\n\n";
            } elseif ($avgHours < 72) {
                $insights .= "🕐 Average conversion time is {$avgHours} hours - typical for considered purchases.\n\n";
            } else {
                $insights .= "🐢 Long conversion window ({$avgHours}h). Consider retargeting campaigns to nudge undecided visitors.\n\n";
            }
        }

        $insights .= "**Recommendation:** Focus on your top-performing campaigns and expand location coverage for higher attribution rates.";

        return $insights;
    }

    /**
     * Clear cached analysis for a user.
     */
    public function clearCache(int $userId): void
    {
        Cache::forget("analysis_attribution_{$userId}");
    }
}
