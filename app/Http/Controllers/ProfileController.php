<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'user' => $this->profileData($user),
        ]);
    }

    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:255'],
            'username' => [
                'sometimes',
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('users', 'username')->ignore($user->id),
            ],
            'email' => [
                'sometimes',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'profile_photo' => ['sometimes', 'nullable', 'image', 'max:5120'],
            'cover_photo' => ['sometimes', 'nullable', 'image', 'max:5120'],
        ]);

        if ($request->hasFile('profile_photo')) {
            $this->deleteStoredFile($user->profile_photo);
            $validated['profile_photo'] = $request->file('profile_photo')->store('profile-photos', 'public');
        }

        if ($request->hasFile('cover_photo')) {
            $this->deleteStoredFile($user->cover_photo);
            $validated['cover_photo'] = $request->file('cover_photo')->store('cover-photos', 'public');
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => $this->profileData($user->fresh()),
        ]);
    }

    private function profileData($user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'username' => $user->username,
            'email' => $user->email,
            'bio' => $user->bio,
            'profile_photo' => $user->profile_photo,
            'profile_photo_url' => $this->fileUrl($user->profile_photo),
            'cover_photo' => $user->cover_photo,
            'cover_photo_url' => $this->fileUrl($user->cover_photo),
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
        ];
    }

    private function fileUrl(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    private function deleteStoredFile(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
