<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;

abstract class BaseCrudController
{
    use ValidatesRequests;
    protected $service;
    protected $viewPath;
    protected $routeName;
    protected $storeformRequestClass;
    protected $updateformRequestClass;
    public function index()
    {
        $items = $this->service->getAllCate();
        return view("{$this->viewPath}.index", compact('items'));
    }

    public function create()
    {
        return view("{$this->viewPath}.create");
    }

    public function store()
    {
        $request = app($this->storeformRequestClass); // Resolve FormRequest
        $data = $request->validated();

        if (method_exists($this, 'beforeStore')) {
            $data = $this->beforeStore($request, $data);
        }

        $this->service->create($data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', 'Thêm mới thành công');
    }
    public function show($id)
    {
        $item = $this->service->find($id);
        return view("{$this->viewPath}.show", compact('item'));
    }
    public function edit($id)
    {
        $item = $this->service->find($id);
        return view("{$this->viewPath}.edit", compact('item'));
    }

    public function update($id)
    {
        $request = app($this->updateformRequestClass);
        $data = $request->validated();

        if (method_exists($this, 'beforeUpdate')) {
            $data = $this->beforeUpdate($request, $data);
        }
        $item = $this->service->find($id); 
        // Update
        $this->service->update($item, $data);

        return redirect()->route($this->routeName . '.index')
            ->with('success', 'Cập nhật thành công');
    }


    public function destroy($id)
    {
        $item = $this->service->find($id);
        $this->service->delete($item);
        return redirect()->route("{$this->routeName}.index")->with('success', 'Đã chuyển vào thùng rác!');
    }
    public function trash()
    {
        $items = $this->service->getTrash();
        return view("{$this->viewPath}.trash", compact('items'));
    }
    public function restore($id)
    {
        $this->service->restore($id);
        return redirect()->route("{$this->routeName}.trash")->with('success', 'Khôi phục thành công');
    }

    public function forceDelete($id)
    {
        $this->service->forceDelete($id);
        return redirect()->route("{$this->routeName}.trash")->with('success', 'Xóa vĩnh viễn thành công');
    }
}
