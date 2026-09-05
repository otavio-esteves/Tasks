<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $team_id
 */
class Category extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['team_id', 'name', 'slug', 'description'];

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function scopeSearch(Builder $query, string $search): Builder
    {
        $term = mb_strtolower(trim($search));

        if ($term === '') {
            return $query;
        }

        return $query->whereRaw('LOWER(name) LIKE ?', ["%{$term}%"]);
    }
}
