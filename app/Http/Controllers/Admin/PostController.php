<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Posts\StorePostRequest;
use App\Http\Requests\Admin\Posts\UpdatePostRequest;
use App\Models\Category;
use App\Models\Post;
use App\Models\Tag;
use App\Traits\LoggableTrait;
use App\Traits\UploadToLocalTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

use function PHPUnit\Framework\isEmpty;

class PostController extends Controller
{
    use LoggableTrait, UploadToLocalTrait;

    const FOLDER = 'blogs';

    public function index(Request $request)
    {
        try {
            $title = 'Quản lý bài viết';
            $subTitle = 'Danh sách bài viết';

            $categories = Category::query()->get();
            $queryPost = Post::with(['user:id,name', 'category:id,name']);
            $posts_total = Post::query()->count();
            $posts_published = Post::query()->where('status', 'published')->count();
            $posts_is_hot = Post::query()->where('is_hot', 1)->count();
            $posts_deleted = Post::onlyTrashed()->count();
            $posts = $queryPost->paginate(10);

            return view('admin.posts.index', compact([
                'title',
                'subTitle',
                'categories',
                'posts',
                'posts_total',
                'posts_published',
                'posts_is_hot',
                'posts_deleted',
            ]));
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Có lỗi xảy ra, vui lòng thử lại');
        }
    }

    public function create()
    {
        try {
            $title = 'Quản lý bài viết';
            $subTitle = 'Thêm mới bài viết';

            $categories = Category::query()->get();
            $tags = Tag::query()->get();

            return view('admin.posts.create', compact([
                'title',
                'subTitle',
                'categories',
                'tags'
            ]));
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function store(StorePostRequest $request)
    {
        try {
            $request->validated();
            DB::beginTransaction();

            $data = $request->except('thumbnail');

            if ($request->hasFile('thumbnail')) {
                $data['thumbnail'] = $this->uploadToLocal($request->file('thumbnail'), self::FOLDER);
            }

            $data['user_id'] = Auth::id();

            if ($request->input('status') === 'scheduled') {
                if (!$request->has('published_at') || empty($request->input('published_at'))) {
                    throw new \Exception('Publish date is required for scheduled posts');
                }
                $data['published_at'] = $request->input('published_at');
            } else {
                $data['published_at'] = $request->input('status') === 'published' ? now() : null;
            }

            do {
                $data['slug'] = Str::slug($request->title) . '-' . substr(Str::uuid(), 0, 10);
            } while (Post::query()->where('slug', $data['slug'])->exists());

            $post = Post::query()->create($data);

            if (!empty($request->input('tags'))) {
                $tags = collect($request->input('tags'))->map(function ($tagName) {
                    return Tag::query()->firstOrCreate([
                        'name' => $tagName,
                        'slug' => Str::slug($tagName) ?? Str::uuid()
                    ]);
                });

                $post->tags()->sync($tags->pluck('id'));
            }

            DB::commit();

            return redirect()->route('admin.posts.index')->with('success', 'Thêm mới bài viết thành công');
        } catch (\Exception $e) {

            DB::rollBack();

            if (
                !empty($data['thumbnail'])
                && filter_var($data['thumbnail'], FILTER_VALIDATE_URL)
            ) {
                $this->deleteFromLocal($data['thumbnail'], 'posts');
            }

            $this->logError($e);

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function show(string $id)
    {
        try {
            $title = 'Quản lý bài viết';
            $subTitle = 'Chi tiết bài viết';

            $post = Post::query()
                ->with(['tags:id,name', 'category:id,name,parent_id', 'user:id,name'])
                ->find($id);

            return view('admin.posts.show', compact([
                'title',
                'subTitle',
                'post',
            ]));
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Không tìm thấy bài viết');
        }
    }

    public function edit(string $id)
    {
        try {
            $title = 'Quản lý bài viết';
            $subTitle = 'Cập nhật bài viết';

            $categories = Category::query()->get();
            $tags = Tag::query()->get();
            $post = Post::query()
                ->with(['tags:id,name', 'category:id,name,parent_id'])
                ->find($id);

            $categoryIds = $post->category->pluck('id')->toArray();
            $tagIds = $post->tags->pluck('id')->toArray();

            return view('admin.posts.edit', compact([
                'title',
                'subTitle',
                'categories',
                'tags',
                'post',
                'categoryIds',
                'tagIds'
            ]));
        } catch (\Exception $e) {
            $this->logError($e);

            return redirect()->back()->with('error', 'Không tìm thấy bài viết');
        }
    }

    public function update(UpdatePostRequest $request, string $id)
    {
        try {
            DB::beginTransaction();

            $data = $request->except('thumbnail', 'categories', 'is_hot');

            $post = Post::query()->with(['tags'])->find($id);

            if ($request->hasFile('thumbnail')) {
                if ($post->thumbnail && filter_var($post->thumbnail, FILTER_VALIDATE_URL)) {
                    $this->deleteFromLocal($post->thumbnail, self::FOLDER);
                }

                $data['thumbnail'] = $this->uploadToLocal($request->file('thumbnail'), self::FOLDER);
            }

            $data['is_hot'] = $request->input('is_hot') ?? 0;
            if ($request->input('status') === 'scheduled') {
                if (!$request->has('published_at') || empty($request->input('published_at'))) {
                    throw new \Exception('Publish date is required for scheduled posts');
                }
                $data['published_at'] = $request->input('published_at');
            } else {
                $data['published_at'] = $request->input('status') === 'published' ? now() : null;
            }

            do {
                $data['slug'] = Str::slug($request->title) . '-' . substr(Str::uuid(), 0, 10);
            } while (Post::query()->where('slug', $data['slug'])->exists());

            $post->update($data);

            if (!empty($request->input('tags'))) {
                $tags = collect($request->input('tags'))->map(function ($tagName) {
                    return Tag::firstOrCreate([
                        'name' => $tagName,
                        'slug' => Str::slug($tagName) ?? Str::uuid()
                    ]);
                });

                $post->tags()->sync($tags->pluck('id'));
            } else {
                $post->tags()->detach();
            }

            DB::commit();

            return redirect()->route('admin.posts.edit', $id)->with('success', 'Cập nhật bài viết thành công');
        } catch (\Exception $e) {
            DB::rollBack();

            if (isset($data['thumbnail']) && !empty($data['thumbnail']) && filter_var($data['thumbnail'], FILTER_VALIDATE_URL)) {
                $this->deleteFromLocal($data['thumbnail'], self::FOLDER);
            }

            $this->logError($e);

            return redirect()->back()->with('error', 'Cập nhật bài viết không thành công');
        }
    }
}
