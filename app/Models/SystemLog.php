<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use MongoDB\Laravel\Eloquent\Model;

#[Fillable(['level', 'message', 'context', 'logged_at'])]
class SystemLog extends Model
{
    protected $connection = 'mongodb';

    protected $collection = 'system_logs';
}
