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
            'variant' => 'nullable|in:profile,destination,tour,hero,logo,generic',
        ]);

        $url = static::storeUploadedImage($request->file('image'), $request->input('variant', 'generic'));

        if (! $url) {
            return self::apiResponse(true, 'Upload Failed', '400', 'Could not store image', []);
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Image uploaded', [
            'url' => $url,
        ]);
    }

    public function storeVideo(Request $request): JsonResponse
    {
        $request->validate([
            'video' => [
                'required',
                'file',
                'max:25600',
                // Browsers/servers often report MP4 as video/mp4, application/mp4, or video/quicktime.
                'mimetypes:video/mp4,video/webm,video/quicktime,application/mp4',
            ],
        ], [
            'video.required' => 'Please choose a video file.',
            'video.mimetypes' => 'Upload an MP4 or WebM video.',
            'video.max' => 'Videos must be 25 MB or smaller.',
        ]);

        $file = $request->file('video');
        if (! $file || ! $file->isValid()) {
            $error = $file?->getErrorMessage() ?: 'Upload failed before the server could store the file.';

            return self::apiResponse(true, 'Upload Failed', '400', $error, []);
        }

        $url = static::storeUploadedVideo($file);

        if (! $url) {
            return self::apiResponse(
                true,
                'Upload Failed',
                '400',
                'Could not store video. Use MP4 or WebM under 25 MB, then try again.',
                []
            );
        }

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Video uploaded', [
            'url' => $url,
        ]);
    }
}
