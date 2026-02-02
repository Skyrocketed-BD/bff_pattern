<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserActivityLog extends Model
{
    use HasFactory;

    protected $table = 'user_activity_logs';

    protected $primaryKey = 'id';

    protected $fillable = [
        'id_users',
        'action',
        'ip_address',
        'user_agent',
        'details',
        'status'
    ];

    protected $casts = [
        'details' => 'array',
    ];
}
