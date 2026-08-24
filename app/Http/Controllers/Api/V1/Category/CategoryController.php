<?php

namespace App\Http\Controllers\Api\V1\Category;

use App\Actions\Category\ListCategoriesAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Category\IndexCategoryRequest;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(IndexCategoryRequest $request, ListCategoriesAction $action): JsonResponse
    {
        $categories = $action($request->validated());

        return ApiResponse::success(CategoryResource::collection($categories), __('api.category.fetched'));
    }

    public function show(Request $request, Category $category): JsonResponse
    {
        return ApiResponse::success(CategoryResource::make($category), __('api.category.fetched'));
    }
}
