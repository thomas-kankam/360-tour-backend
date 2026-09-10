<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutCms;
use App\Traits\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminAboutCmsController extends Controller
{
    use Helpers;

    public function show(): JsonResponse
    {
        $record = AboutCms::current();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'About CMS retrieved', [
            'draft' => $record->editorDraft(),
            'published' => $record->published_content
                ? $record->mergeWithDefaults($record->published_content)
                : null,
            'meta' => $record->meta(),
        ]);
    }

    public function updateDraft(Request $request): JsonResponse
    {
        $content = $this->validatedContent($request);
        $record = AboutCms::current();

        $record->update([
            'draft_content' => $content,
            'draft_updated_at' => now(),
        ]);

        $record->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'About CMS draft saved', [
            'draft' => $record->editorDraft(),
            'meta' => $record->meta(),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = AboutCms::current();
        $admin = request()->user();

        $content = $request->has('content')
            ? $this->validatedContent($request)
            : $record->mergeWithDefaults(
                $record->draft_content ?? $record->published_content ?? AboutCms::defaultContent()
            );

        $content = $record->mergeWithDefaults($content);

        $record->update([
            'draft_content' => $content,
            'published_content' => $content,
            'draft_updated_at' => now(),
            'published_at' => now(),
            'published_by' => $admin->admin_slug ?? null,
        ]);

        $record->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'About CMS published', [
            'published' => $record->mergeWithDefaults($record->published_content ?? []),
            'meta' => $record->meta(),
        ]);
    }

    public function reset(): JsonResponse
    {
        $defaults = AboutCms::defaultContent();
        $record = AboutCms::current();

        $record->update([
            'draft_content' => $defaults,
            'draft_updated_at' => now(),
        ]);

        $record->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'About CMS draft reset to defaults', [
            'draft' => $record->editorDraft(),
            'meta' => $record->meta(),
        ]);
    }

    protected function validatedContent(Request $request): array
    {
        $data = $request->validate([
            'content' => 'required|array',
        ]);

        return AboutCms::current()->mergeWithDefaults($data['content']);
    }
}
