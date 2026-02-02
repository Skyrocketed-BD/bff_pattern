<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserNotification extends Model
{
    use HasFactory;

    protected $table = 'user_notifications';

    protected $primaryKey = 'id_user_notifications';

    protected $fillable = [
        'id_users',
        'title',
        'message',
        'url',
        'status',
    ];
}
