<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;
use Override;

class UserRepository implements UserRepositoryInterface
{
    #[Override]
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    #[Override]
    public function findByName(string $name): ?User
    {
        return User::where('name', $name)->first();
    }

    #[Override]
    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    #[Override]
    public function findByField(string $field, string $value): ?User
    {
        $allowdFields = ['name', 'email', 'phone'];

        if (! in_array($field, $allowdFields, true)) {
            return null;
        }

        return User::where($field, $value)->first();
    }

    #[Override]
    public function create(array $data): User
    {
        return User::create($data);
    }

    #[Override]
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user->refresh();
    }

    #[Override]
    public function delete(User $user): void
    {
        $user->delete();
    }
}
