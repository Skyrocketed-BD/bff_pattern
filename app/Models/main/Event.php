<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $table = 'events';

    protected $primaryKey = 'id_event';

    protected $fillable = [
        'event',
        'title',
        'message',
        'url',
        'image',
    ];

    // relasi ke table event_users
    public function toEventUsers()
    {
        return $this->hasMany(EventUser::class, 'id_event');
    }
}
