<?php

namespace App\Http\Controllers;

use App\Models\ApiKey;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiKeyController extends Controller
{
    /**
     * Display a listing of API keys.
     */
    public function index(Request $request): Response
    {
        $apiKeys = ApiKey::query()
            ->where('user_id', $request->user()->id)
            ->withCount('events')
            ->latest()
            ->get();

        return Inertia::render('api-keys/index', [
            'apiKeys' => $apiKeys,
        ]);
    }

    /**
     * Store a newly created API key.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $result = ApiKey::generateKey($request->user(), $validated['name']);

        // Return with the plain key (only shown once!)
        return redirect()->route('api-keys.index')
            ->with('success', 'API key created successfully!')
            ->with('newKey', $result['plain_key']);
    }

    /**
     * Revoke (deactivate) an API key.
     */
    public function destroy(Request $request, ApiKey $apiKey)
    {
        if ($apiKey->user_id !== $request->user()->id) {
            abort(403);
        }

        $apiKey->update(['is_active' => false]);

        return redirect()->route('api-keys.index')
            ->with('success', 'API key revoked successfully!');
    }
}
