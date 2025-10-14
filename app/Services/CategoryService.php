<?php

namespace App\Services;

use App\Models\Category;

class CategoryService extends BaseCrudService
{
    public function __construct(Category $category)
    {
        $this->model = $category;
    }
    public function getAllCate($orderBy = 'id', $direction = 'desc')
    {
        return $this->model->with('children.children')->orderBy($orderBy, $direction)->paginate(10);
    }
    public function getAll($orderBy = 'id', $direction = 'desc')
    {
        return $this->model->with('children.children')->orderBy($orderBy, $direction)->get();
    }
}
