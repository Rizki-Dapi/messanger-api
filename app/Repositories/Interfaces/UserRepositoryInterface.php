<?php

namespace App\Repositories\Interfaces;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findById(int $id): ?User;

    public function findByName(string $name): ?User;

    public function findByPhone(string $phone): ?User;

    public function findByField(string $field, string $value): ?User;

    public function create(array $data): User;

    public function update(User $user, array $data): User;

    public function delete(User $user): void;
}
