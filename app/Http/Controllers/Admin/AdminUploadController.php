<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUploadController extends Controller
{
    public function storeImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|file|image|mimes:jpeg,jpg,png,gif,webp|max:10240',
        ]);

        $url = static::storeUploadedImage($request->file('image'));

        if (! $url) {
            return self::apiResponse(true, 'Upload Failed', '400', 'Could not store image', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Image uploaded', [
            'url' => $url,
        ]);
    }
}
