<?php

namespace App\Repositories\Interfaces;

interface LogRepositoryInterface
{
    public function record(array $data): void;
}
