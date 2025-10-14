<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(){
        $categories = Category::with('parent:id,name')
                        ->select('id','name','slug','parent_id')
                        ->where('status',1)
                        ->oldest('id')
                        ->paginate(9);
        return response()->json([
            'status' => true,
            'categories' => $categories
        ]);
    }
}
