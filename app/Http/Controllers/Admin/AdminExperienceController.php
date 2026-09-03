<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Experience;
use App\Traits\Helpers;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminExperienceController extends Controller
{
    use Helpers;

    public function index(Request $request): JsonResponse
    {
        $status = trim((string) $request->query('status', ''));
        $query = Experience::query()->orderBy('sort_order')->orderBy('id');

        if (in_array($status, ['draft', 'published'], true)) {
            $query->where('status', $status);
        }

        $items = $query->get()->map(fn (Experience $item) => $item->toExperienceArray(includeStatus: true))->values()->all();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experiences retrieved', [
            'items' => $items,
        ]);
    }

    public function show(Experience $experience): JsonResponse
    {
        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experience retrieved', [
            'experience' => $experience->toExperienceArray(includeStatus: true),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatedPayload($request);
        $experience = Experience::query()->create($data);
        $experience->persistImagePath();
        $experience->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_CREATED, 'Experience created', [
            'experience' => $experience->toExperienceArray(includeStatus: true),
        ]);
    }

    public function update(Request $request, Experience $experience): JsonResponse
    {
        $data = $this->validatedPayload($request, $experience);
        $experience->update($data);
        $experience->persistImagePath();
        $experience->refresh();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experience updated', [
            'experience' => $experience->toExperienceArray(includeStatus: true),
        ]);
    }

    public function destroy(Experience $experience): JsonResponse
    {
        $experience->delete();

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experience deleted', []);
    }

    public function publish(Experience $experience): JsonResponse
    {
        $experience->update([
            'status' => 'published',
            'published_at' => $experience->published_at ?? now(),
        ]);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experience published', [
            'experience' => $experience->fresh()->toExperienceArray(includeStatus: true),
        ]);
    }

    public function unpublish(Experience $experience): JsonResponse
    {
        $experience->update(['status' => 'draft']);

        return self::apiResponse(false, 'Action Successful', (string) self::API_SUCCESS, 'Experience saved as draft', [
            'experience' => $experience->fresh()->toExperienceArray(includeStatus: true),
        ]);
    }

    protected function validatedPayload(Request $request, ?Experience $experience = null): array
    {
        $data = $request->validate([
            'key' => [
                'nullable',
                'string',
                'max:80',
                Rule::unique('experiences', 'experience_key')->ignore($experience?->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:180',
                Rule::unique('experiences', 'slug')->ignore($experience?->id),
            ],
            'label' => 'required|string|max:160',
            'iconKey' => 'nullable|string|max:40',
            'tagline' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'highlights' => 'nullable|array',
            'highlights.*' => 'string',
            'regions' => 'nullable|array',
            'regions.*' => 'string',
            'keywords' => 'nullable|array',
            'keywords.*' => 'string',
            'image' => 'nullable|string',
            'badgeText' => 'nullable|string|max:80',
            'tourQuery' => 'nullable|array',
            'storyCategory' => 'nullable|string|max:64',
            'relatedStorySlugs' => 'nullable|array',
            'relatedStorySlugs.*' => 'string',
            'status' => 'nullable|in:draft,published',
            'sortOrder' => 'nullable|integer|min:0',
        ]);

        $label = $data['label'];
        $key = Str::slug($data['key'] ?? $label, '-');
        if ($key === '') {
            $key = 'experience-'.Str::lower(Str::random(6));
        }
        $slug = Str::slug($data['slug'] ?? $label);
        if ($slug === '') {
            $slug = $key;
        }

        $status = $data['status'] ?? 'draft';
        $image = static::persistCmsImageValue($data['image'] ?? '', 'destination');

        return [
            'experience_key' => $key,
            'slug' => $slug,
            'label' => $label,
            'icon_key' => $data['iconKey'] ?? 'compass',
            'tagline' => $data['tagline'] ?? '',
            'description' => $data['description'] ?? '',
            'highlights' => array_values($data['highlights'] ?? []),
            'regions' => array_values($data['regions'] ?? []),
            'keywords' => array_values($data['keywords'] ?? []),
            'image' => $image,
            'badge_text' => $data['badgeText'] ?? '',
            'tour_query' => $data['tourQuery'] ?? [],
            'story_category' => $data['storyCategory'] ?? '',
            'related_story_slugs' => array_values($data['relatedStorySlugs'] ?? []),
            'status' => $status,
            'published_at' => $status === 'published' ? ($experience?->published_at ?? now()) : $experience?->published_at,
            'sort_order' => (int) ($data['sortOrder'] ?? $experience?->sort_order ?? 0),
        ];
    }
}
