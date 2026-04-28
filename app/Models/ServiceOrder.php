<?php

namespace App\Models;

use App\Domain\ServiceOrders\Exceptions\InvalidServiceOrderStatusTransition;
use App\Domain\ServiceOrders\ServiceOrderStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @property ServiceOrderStatus $status
 */
class ServiceOrder extends Model
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
        'secretariat_id',
        'category_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $serviceOrder): void {
            if (blank($serviceOrder->code)) {
                $serviceOrder->code = self::temporaryCode();
            }

            if (blank($serviceOrder->status)) {
                $serviceOrder->status = ServiceOrderStatus::Pending;
            }
        });

        static::created(function (self $serviceOrder): void {
            $permanentCode = self::codeFromId($serviceOrder->id);

            if ($serviceOrder->code !== $permanentCode) {
                $serviceOrder->forceFill(['code' => $permanentCode])->saveQuietly();
                $serviceOrder->code = $permanentCode;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'due_date' => 'date',
            'is_urgent' => 'boolean',
            'status' => ServiceOrderStatus::class,
        ];
    }

    public function secretariat(): BelongsTo
    {
        return $this->belongsTo(Secretariat::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * @return HasMany<OdsChecklist, $this>
     */
    public function checklistItems(): HasMany
    {
        return $this->hasMany(OdsChecklist::class)->orderBy('sort_order')->orderBy('id');
    }

    /**
     * @return HasMany<OdsHistory, $this>
     */
    public function histories(): HasMany
    {
        return $this->hasMany(OdsHistory::class)->orderBy('created_at', 'desc')->orderBy('id', 'desc');
    }

    public function scopeForSecretariat(Builder $query, int $secretariatId): Builder
    {
        return $query->where('secretariat_id', $secretariatId);
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
        return 'ODS-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT);
    }

    public function changeStatus(ServiceOrderStatus $status): void
    {
        if (! $this->status->canTransitionTo($status)) {
            throw new InvalidServiceOrderStatusTransition($this->status, $status);
        }

        $this->forceFill(['status' => $status])->saveQuietly();
        $this->status = $status;
    }

    private static function temporaryCode(): string
    {
        return 'TMP-'.Str::upper((string) Str::ulid());
    }
}
