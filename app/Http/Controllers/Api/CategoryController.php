<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        $categories = Category::latest('id')->paginate(9);
        return response()->json([
            'status' => true,
            'categories' => $categories
        ]);
    }
}
