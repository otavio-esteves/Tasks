<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskChecklist extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'label',
        'is_completed',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'is_completed' => 'boolean',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }
}
