<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventUser extends Model
{
    use HasFactory;

    protected $table = 'event_users';

    protected $primaryKey = 'id_event_user';

    protected $fillable = [
        'id_event',
        'id_users',
    ];
}
