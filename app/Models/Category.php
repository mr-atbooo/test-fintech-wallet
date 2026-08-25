<?php

namespace App\Models;

use App\Enums\Status;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $attributes = [
        'status' => Status::Active->value,
    ];

    protected $fillable = [
        'parent_id',
        'name',
        'type',
        'icon',
        'color',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'status' => Status::class,
        ];
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
