<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Team extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description'];

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
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
