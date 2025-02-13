<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AIProvider;
use App\Models\AIModel;
use Illuminate\Http\Request;
use App\Enums\AIProviderType;

class AdminController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
        $this->middleware('super.admin');
    }

    public function listProviders()
    {
        $providers = AIProvider::with('models')->get();
        
        return response()->json([
            'success' => true,
            'data' => $providers
        ]);
    }

    public function addProvider(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:ai_providers,name',
            'api_key' => 'required|string',
            'base_url' => 'required|url',
            'implementation_class' => 'required|string'
        ]);

        $provider = AIProvider::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $provider
        ], 201);
    }

    public function addModel(Request $request)
    {
        $request->validate([
            'provider_id' => 'required|exists:ai_providers,id',
            'name' => 'required|string',
            'display_name' => 'required|string',
            'endpoint' => 'required|string',
            'min_temperature' => 'required|numeric|min:0|max:1',
            'max_temperature' => 'required|numeric|min:0|max:1',
            'default_temperature' => 'required|numeric|min:0|max:1',
            'can_reason' => 'boolean',
            'can_access_web' => 'boolean',
            'is_active' => 'boolean',
            'additional_settings' => 'nullable|json'
        ]);

        $model = AIModel::create($request->all());

        return response()->json([
            'success' => true,
            'data' => $model
        ], 201);
    }
} 