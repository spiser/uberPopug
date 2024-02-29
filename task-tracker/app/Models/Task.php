<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int    $id
 * @property string $user_id
 * @property string $description
 * @property string $status
 * @property string $public_id
 */
class Task extends Model
{
    protected $attributes = [
        'status' => TaskStatus::processing,
    ];
    protected $fillable = [
        'user_id',
        'description',
        'status'
    ];

    protected $casts = [
        'status' => TaskStatus::class
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
