<?php

namespace App\Actions\Category;

use App\Services\Category\CategoryService;
use Illuminate\Database\Eloquent\Collection;

class ListCategoriesAction
{
    public function __construct(private readonly CategoryService $categoryService) {}

    public function __invoke(array $filters): Collection
    {
        return $this->categoryService->list($filters);
    }
}
