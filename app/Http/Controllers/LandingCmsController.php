<?php

namespace App\Http\Controllers;

use App\Models\LandingCms;
use Illuminate\Http\JsonResponse;

class LandingCmsController extends Controller
{
    public function show(): JsonResponse
    {
        $record = LandingCms::current();

        if ($record->published_content === null) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'No published landing CMS content', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Landing CMS retrieved', [
            'content' => $record->mergeWithDefaults($record->published_content),
            'published_at' => $record->published_at?->toIso8601String(),
        ]);
    }
}
