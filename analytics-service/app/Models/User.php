<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * @property int      $id
 * @property string   $name
 * @property string   $email
 * @property UserRole $role
 * @property bool     $active
 * @property integer  $balance
 * @property string   $public_id
 */
class User extends Authenticatable
{
    protected $attributes = [
        'balance' => 0,
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'active',
        'public_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'active' => 'boolean',
        'role' => UserRole::class
    ];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
