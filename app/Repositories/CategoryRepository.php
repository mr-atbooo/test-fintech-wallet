<?php

namespace App\Repositories;

use App\Enums\Status;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryRepository
{
    public function allActive(?string $type = null): Collection
    {
        return Category::query()
            ->where('status', Status::Active->value)
            ->when($type, fn ($query, $type) => $query->where('type', $type))
            ->orderBy('parent_id')
            ->orderBy('name')
            ->get();
    }
}
