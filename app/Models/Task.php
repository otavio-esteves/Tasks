<?php

namespace App\Models;

use App\Domain\Tasks\Exceptions\InvalidTaskStatusTransition;
use App\Domain\Tasks\TaskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property TaskStatus $status
 * @property int $team_id
 */
class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'title',
        'location',
        'observation',
        'due_date',
        'is_urgent',
        'status',
        'team_id',
        'category_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $task): void {
            if (blank($task->code)) {
                $task->code = self::temporaryCode();
            }

            if (blank($task->status)) {
                $task->status = TaskStatus::Pending;
            }
        });

        static::created(function (self $task): void {
            $permanentCode = self::codeFromId($task->id);

            if ($task->code !== $permanentCode) {
                $task->forceFill(['code' => $permanentCode])->saveQuietly();
                $task->code = $permanentCode;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_urgent' => 'boolean',
            'status' => TaskStatus::class,
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<TaskChecklist, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(TaskChecklist::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<TaskHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(TaskHistory::class)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    public function scopeForTeam(Builder $query, int $teamId): Builder
    {
        return $query->where('team_id', $teamId);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $term = mb_strtolower(trim($search));

        if ($term === '') {
            return $query;
        }

        return $query->where(function (Builder $query) use ($term): void {
            $like = "%{$term}%";

            $query->whereRaw('LOWER(code) LIKE ?', [$like])
                ->orWhereRaw('LOWER(title) LIKE ?', [$like])
                ->orWhereRaw('LOWER(location) LIKE ?', [$like]);
        });
    }

    public static function codeFromId(int $id): string
    {
        return 'TASK-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function changeStatus(TaskStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new InvalidTaskStatusTransition($this->status, $status);
        }

        $this->forceFill(['status' => $status])->saveQuietly();
        $this->status = $status;
    }

    private static function temporaryCode(): string
    {
        return 'TMP-'.Str::upper((string) Str::ulid());
    }
}
