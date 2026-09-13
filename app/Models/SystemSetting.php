<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $fillable = [
        'login_required',
        'public_user_id',
    ];

    protected function casts(): array
    {
        return [
            'login_required' => 'boolean',
        ];
    }

    public function publicUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'public_user_id');
    }
}
