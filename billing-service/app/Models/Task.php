<?php

namespace App\Models;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use phpseclib3\Crypt\Random;

/**
 * @property int    $id
 * @property string $user_id
 * @property string $description
 * @property integer $assigned_cost
 * @property integer $completed_cost
 * @property string $public_id
 */
class Task extends Model
{
    protected $fillable = [
        'description',
        'assigned_cost',
        'completed_cost'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
