<?php

namespace App\Http\Controllers;

use App\Models\AboutCms;
use Illuminate\Http\JsonResponse;

class AboutCmsController extends Controller
{
    public function show(): JsonResponse
    {
        $record = AboutCms::current();

        if ($record->published_content === null) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'No published about CMS content', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'About CMS retrieved', [
            'content' => $record->mergeWithDefaults($record->published_content),
            'published_at' => $record->published_at?->toIso8601String(),
        ]);
    }
}
