<?php

namespace App\Repositories;

use App\Models\ActivityLog;
use App\Repositories\Interfaces\LogRepositoryInterface;

class LogRepository implements LogRepositoryInterface
{
    public function record(array $data): void
    {
        try {
            ActivityLog::create([
                'user_id' => $data['user_id'] ?? null,
                'action' => $data['action'],
                'context' => $data['context'] ?? null,
                'ip_address' => $data['ip_address'] ?? request()->ip(),
            ]);
        } catch (\throwable $e) {
            report($e);
        }
    }
}
