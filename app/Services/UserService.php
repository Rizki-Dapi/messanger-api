<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Interfaces\LogRepositoryInterface;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class UserService
{
    /**
     * Create a new class instance.
     */
    public function __construct(
        private readonly UserRepositoryInterface $userRepo,
        private readonly LogRepositoryInterface $logRepo,
    ) {}

    public function updateName(User $user, string $name): User
    {
        $data = $this->userRepo->update($user, ['name' => $name]);

        $this->logRepo->record([
            'user_id' => $data->id,
            'action' => 'user.updated_name',
        ]);

        return $data;
    }

    public function updateProfilePhoto(UploadedFile $photo, User $user): User
    {
        $newPath = $photo->store('profile-photos', 'public');

        if ($newPath === false) {
            throw new RuntimeException('Failed to save profile photo.');
        }

        $oldPath = $user->profile_photo_path;

        try {
            $updatedUser = DB::transaction(
                fn () => $this->userRepo->update($user, ['profile_photo_path' => $newPath])
            );
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($newPath);

            throw $e;
        }

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $updatedUser;
    }

    public function deleteProfilePhoto(User $user): User
    {
        $oldPath = $user->profile_photo_path;

        if (! $oldPath) {
            return $user;
        }

        $updatedUser = $this->userRepo->update($user, ['profile_photo_path' => null]);

        Storage::disk('public')->delete($oldPath);

        return $updatedUser;
    }
}
