<?php

namespace App\Http\Controllers;

use App\Models\Experience;
use Illuminate\Http\JsonResponse;

class ExperienceController extends Controller
{
    public function index(): JsonResponse
    {
        $items = Experience::query()
            ->published()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (Experience $experience) => $experience->toExperienceArray())
            ->values()
            ->all();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experiences retrieved', [
            'items' => $items,
        ]);
    }
}
