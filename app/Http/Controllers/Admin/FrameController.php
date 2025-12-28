<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Frame;
use Illuminate\Http\Request;

class FrameController extends Controller
{
    public function index()
    {
        $frames = Frame::latest('id')->paginate(10);
        return view('admin.frames.index', compact('frames'));
    }
    public function create()
    {
        return view('admin.frames.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'top_background' => 'nullable|image',
            'bottom_background' => 'nullable|image',
            'ribbon_image' => 'nullable|image',
            'left_decor_image' => 'nullable|image',
            'right_decor_image' => 'nullable|image',
        ]);

        $data = $request->only('name');

        $uploadFields = [
            'top_background',
            'bottom_background',
            'ribbon_image',
            'left_decor_image',
            'right_decor_image',
        ];

        foreach ($uploadFields as $field) {
            if ($request->hasFile($field)) {
                $data[$field] = $request->file($field)->store('frames', 'public');
            }
        }

        Frame::create($data);

        return redirect()
            ->route('admin.frames.index')
            ->with('success', 'Thêm khung thành công');
    }
}
