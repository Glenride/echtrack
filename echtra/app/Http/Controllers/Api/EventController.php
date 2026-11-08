<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAttribution;
use App\Models\Beacon;
use App\Models\Event;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class EventController extends Controller
{
    /**
     * Store a new event from the SDK.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'beacon_id' => 'required|string|max:16',
            'device_id' => 'required|string|max:64',
            'signal_strength_db' => 'required|numeric|min:-150|max:0',
            'timestamp' => 'required|date',
            'metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        // Find the beacon
        $beacon = Beacon::where('beacon_id', $request->beacon_id)
            ->active()
            ->first();

        if (!$beacon) {
            return response()->json([
                'error' => 'Beacon not found',
                'message' => "No active beacon found with ID: {$request->beacon_id}",
            ], 404);
        }

        // Hash the device ID for privacy
        $hashedDeviceId = hash('sha256', $request->device_id);

        // Create the event
        $event = Event::create([
            'beacon_id' => $beacon->id,
            'device_id' => $hashedDeviceId,
            'signal_strength_db' => $request->signal_strength_db,
            'heard_at' => $request->timestamp,
            'api_key_id' => $request->api_key->id,
            'metadata' => $request->metadata,
        ]);

        // Dispatch attribution processing for location beacons
        if ($beacon->type === 'location') {
            ProcessAttribution::dispatch($event);
        }

        return response()->json([
            'success' => true,
            'event_id' => $event->id,
            'beacon_type' => $beacon->type,
        ], 201);
    }

    /**
     * Store multiple events in batch from the SDK.
     */
    public function storeBatch(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'events' => 'required|array|min:1|max:100',
            'events.*.beacon_id' => 'required|string|max:16',
            'events.*.device_id' => 'required|string|max:64',
            'events.*.signal_strength_db' => 'required|numeric|min:-150|max:0',
            'events.*.timestamp' => 'required|date',
            'events.*.metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Validation failed',
                'messages' => $validator->errors(),
            ], 422);
        }

        $results = [];
        $successCount = 0;
        $failureCount = 0;

        foreach ($request->events as $index => $eventData) {
            $beacon = Beacon::where('beacon_id', $eventData['beacon_id'])
                ->active()
                ->first();

            if (!$beacon) {
                $results[] = [
                    'index' => $index,
                    'success' => false,
                    'error' => "Beacon not found: {$eventData['beacon_id']}",
                ];
                $failureCount++;
                continue;
            }

            $hashedDeviceId = hash('sha256', $eventData['device_id']);

            $event = Event::create([
                'beacon_id' => $beacon->id,
                'device_id' => $hashedDeviceId,
                'signal_strength_db' => $eventData['signal_strength_db'],
                'heard_at' => $eventData['timestamp'],
                'api_key_id' => $request->api_key->id,
                'metadata' => $eventData['metadata'] ?? null,
            ]);

            if ($beacon->type === 'location') {
                ProcessAttribution::dispatch($event);
            }

            $results[] = [
                'index' => $index,
                'success' => true,
                'event_id' => $event->id,
            ];
            $successCount++;
        }

        return response()->json([
            'success' => $failureCount === 0,
            'total' => count($request->events),
            'success_count' => $successCount,
            'failure_count' => $failureCount,
            'results' => $results,
        ], $failureCount === 0 ? 201 : 207);
    }

    /**
     * Validate if a beacon ID exists and is active.
     */
    public function validateBeacon(string $beaconId): JsonResponse
    {
        $beacon = Beacon::where('beacon_id', $beaconId)
            ->active()
            ->first();

        if (!$beacon) {
            return response()->json([
                'valid' => false,
                'message' => 'Beacon not found or inactive',
            ], 404);
        }

        return response()->json([
            'valid' => true,
            'type' => $beacon->type,
            'name' => $beacon->name,
        ]);
    }
}
