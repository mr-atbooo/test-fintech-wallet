<?php

namespace App\Services\Category;

use App\Repositories\CategoryRepository;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function __construct(private readonly CategoryRepository $categories) {}

    public function list(array $filters): Collection
    {
        return $this->categories->allActive($filters['type'] ?? null);
    }
}
