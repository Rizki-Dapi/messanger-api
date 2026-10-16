<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['user_id', 'action', 'context', 'ip_address'])]
class ActivityLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'activity_logs';
}
