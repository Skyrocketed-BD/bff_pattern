<?php

namespace App\Models\main;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserTokens extends Model
{
    use HasFactory;

    protected $table = 'user_tokens';

    protected $primaryKey = 'id_user_tokens';

    protected $fillable = [
        'id_users',
        'token',
    ];
}
