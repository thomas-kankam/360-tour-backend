<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingCms;
use App\Traits\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminLandingCmsController extends Controller
{
    use Helpers;

    public function show(): JsonResponse
    {
        $record = LandingCms::current();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Landing CMS retrieved', [
            'draft' => $record->editorDraft(),
            'published' => $record->published_content ? $record->mergeWithDefaults($record->published_content) : null,
            'meta' => $record->meta(),
        ]);
    }

    public function updateDraft(Request $request): JsonResponse
    {
        $content = $this->validatedContent($request);
        $record = LandingCms::current();

        $record->update([
            'draft_content' => $content,
            'draft_updated_at' => now(),
        ]);

        $record->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Landing CMS draft saved', [
            'draft' => $record->editorDraft(),
            'meta' => $record->meta(),
        ]);
    }

    public function publish(Request $request): JsonResponse
    {
        $record = LandingCms::current();
        $admin = request()->user();

        $content = $request->has('content')
            ? $this->validatedContent($request)
            : ($record->draft_content ?? $record->published_content ?? LandingCms::defaultContent());

        $content = $record->mergeWithDefaults($content);

        $record->update([
            'draft_content' => $content,
            'published_content' => $content,
            'draft_updated_at' => now(),
            'published_at' => now(),
            'published_by' => $admin->admin_slug,
        ]);

        $record->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Landing CMS published', [
            'published' => $record->mergeWithDefaults($record->published_content ?? []),
            'meta' => $record->meta(),
        ]);
    }

    public function reset(): JsonResponse
    {
        $defaults = LandingCms::defaultContent();
        $record = LandingCms::current();

        $record->update([
            'draft_content' => $defaults,
            'draft_updated_at' => now(),
        ]);

        $record->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Landing CMS draft reset to defaults', [
            'draft' => $record->editorDraft(),
            'meta' => $record->meta(),
        ]);
    }

    protected function validatedContent(Request $request): array
    {
        $data = $request->validate([
            'content' => 'required|array',
            'content.hero' => 'required|array',
            'content.tours' => 'required|array',
            'content.destinations' => 'required|array',
            'content.regions' => 'required|array',
            'content.explore' => 'required|array',
            'content.cta' => 'required|array',
        ]);

        $content = LandingCms::current()->mergeWithDefaults($data['content']);

        if (! empty($content['hero']['backgroundImage']) && str_starts_with($content['hero']['backgroundImage'], 'data:')) {
            $content['hero']['backgroundImage'] = static::base64ImageDecode($content['hero']['backgroundImage']) ?? $content['hero']['backgroundImage'];
        }

        if (! empty($content['cta']['image']) && str_starts_with($content['cta']['image'], 'data:')) {
            $content['cta']['image'] = static::base64ImageDecode($content['cta']['image']) ?? $content['cta']['image'];
        }

        return $content;
    }
}
