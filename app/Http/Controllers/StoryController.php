<?php

namespace App\Http\Controllers;

use App\Models\Story;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Story::query()->published()->orderBy('sort_order')->orderByDesc('published_at')->orderByDesc('id');

        if ($category = trim((string) $request->query('category', ''))) {
            if (strcasecmp($category, 'all') !== 0) {
                $query->where('category', $category);
            }
        }

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('country', 'like', "%{$search}%")
                    ->orWhere('category', 'like', "%{$search}%")
                    ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }

        $items = $query->get()->map(fn (Story $story) => $story->toStoryArray())->values()->all();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Stories retrieved', [
            'items' => $items,
        ]);
    }

    public function show(string $slug): JsonResponse
    {
        $story = Story::query()->published()->where('slug', $slug)->first();

        if (! $story) {
            return self::apiResponse(true, 'Action Unsuccessful', (string) self::API_NOT_FOUND, 'Story not found', []);
        }

        $related = Story::query()
            ->published()
            ->where('category', $story->category)
            ->where('id', '!=', $story->id)
            ->orderBy('sort_order')
            ->limit(3)
            ->get()
            ->map(fn (Story $item) => $item->toStoryArray())
            ->values()
            ->all();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Story retrieved', [
            'story' => $story->toStoryArray(),
            'related' => $related,
        ]);
    }
}
