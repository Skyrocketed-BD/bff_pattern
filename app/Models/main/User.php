<?php

namespace App\Models\main;

use App\Models\operation\Kontraktor;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    use SoftDeletes;

    // untuk default tabel
    protected $table = 'users';

    // untuk default id
    protected $primaryKey = 'id_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'id_users',
        'id_kontraktor',
        'id_role',
        'name',
        'email',
        'username',
        'password',
        'gender',
        'birth_date',
        'phone',
        'address',
        'avatar',
        'is_active',
        'is_logged_in',
        'count_logged_in',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
            'id_role'           => 'integer',
            'id_users'          => 'integer',
        ];
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // untuk relasi
    protected $with = ['toUserNotifications', 'toRole.toRoleAccess.toMenuBody.toMenuCategory.toMenuModule'];

    // relasi ke tabel kontraktor
    public function toKontraktor()
    {
        return $this->setConnection('operation')->belongsTo(Kontraktor::class, 'id_kontraktor', 'id_kontraktor');
    }

    // relasi ke tabel role
    public function toRole()
    {
        return $this->belongsTo(Role::class, 'id_role', 'id_role');
    }

    // relasi ke tabel user_notifications
    public function toUserNotifications()
    {
        return $this->hasMany(UserNotification::class, 'id_users', 'id_users');
    }
}
