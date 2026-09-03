<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Story;
use App\Traits\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminStoryController extends Controller
{
    use Helpers;

    public function index(Request $request): JsonResponse
    {
        $status = trim((string) $request->query('status', ''));
        $query = Story::query()->orderBy('sort_order')->orderByDesc('id');

        if (in_array($status, ['draft', 'published'], true)) {
            $query->where('status', $status);
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%");
            });
        }

        $items = $query->get()->map(fn (Story $story) => $story->toStoryArray(includeStatus: true))->values()->all();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Stories retrieved', [
            'items' => $items,
        ]);
    }

    public function show(Story $story): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Story retrieved', [
            'story' => $story->toStoryArray(includeStatus: true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedPayload($request);
        $story = Story::query()->create($data);
        $story->persistImagePath();
        $story->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Story created', [
            'story' => $story->toStoryArray(includeStatus: true),
        ]);
    }

    public function update(Request $request, Story $story): JsonResponse
    {
        $data = $this->validatedPayload($request, $story);
        $story->update($data);
        $story->persistImagePath();
        $story->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Story updated', [
            'story' => $story->toStoryArray(includeStatus: true),
        ]);
    }

    public function destroy(Story $story): JsonResponse
    {
        $story->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Story deleted', []);
    }

    public function publish(Story $story): JsonResponse
    {
        $story->update([
            'status' => 'published',
            'published_at' => $story->published_at ?? now(),
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Story published', [
            'story' => $story->fresh()->toStoryArray(includeStatus: true),
        ]);
    }

    public function unpublish(Story $story): JsonResponse
    {
        $story->update(['status' => 'draft']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Story saved as draft', [
            'story' => $story->fresh()->toStoryArray(includeStatus: true),
        ]);
    }

    protected function validatedPayload(Request $request, ?Story $story = null): array
    {
        $data = $request->validate([
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('stories', 'slug')->ignore($story?->id),
            ],
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string',
            'category' => 'nullable|string|max:64',
            'country' => 'nullable|string|max:120',
            'author' => 'nullable|string|max:160',
            'authorRole' => 'nullable|string|max:160',
            'date' => 'nullable|string|max:64',
            'readTime' => 'nullable|string|max:40',
            'image' => 'nullable|string',
            'body' => 'nullable|array',
            'body.*.type' => 'required_with:body|string|in:lead,heading,paragraph,quote,list',
            'body.*.text' => 'nullable|string',
            'body.*.items' => 'nullable|array',
            'body.*.items.*' => 'string',
            'status' => 'nullable|in:draft,published',
            'sortOrder' => 'nullable|integer|min:0',
        ]);

        $slug = Str::slug($data['slug'] ?? $data['title']);
        if ($slug === '') {
            $slug = 'story-'.Str::lower(Str::random(8));
        }

        $status = $data['status'] ?? 'draft';
        $image = static::persistCmsImageValue($data['image'] ?? '', 'destination');

        return [
            'slug' => $slug,
            'title' => $data['title'],
            'excerpt' => $data['excerpt'] ?? '',
            'category' => $data['category'] ?? '',
            'country' => $data['country'] ?? '',
            'author' => $data['author'] ?? '',
            'author_role' => $data['authorRole'] ?? '',
            'display_date' => $data['date'] ?? '',
            'read_time' => $data['readTime'] ?? '',
            'image' => $image,
            'body' => array_values($data['body'] ?? []),
            'status' => $status,
            'published_at' => $status === 'published' ? ($story?->published_at ?? now()) : $story?->published_at,
            'sort_order' => (int) ($data['sortOrder'] ?? $story?->sort_order ?? 0),
        ];
    }
}
