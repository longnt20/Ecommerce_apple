<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\User::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(\App\Models\Category::class)->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('content')->nullable();
            $table->string('thumbnail')->nullable();
            $table->enum('status', [
                \App\Models\Post::STATUS_DRAFT,
                \App\Models\Post::STATUS_PENDING,
                \App\Models\Post::STATUS_PUBLISHED,
                \App\Models\Post::STATUS_PRIVATE,
                \App\Models\Post::STATUS_SCHEDULED,
            ])->default(\App\Models\Post::STATUS_DRAFT);
            $table->integer('views')->default(0);
            $table->boolean('is_hot')->default(false);
            $table->timestamp('published_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->fullText([
                'title',
                'description',
                'content',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('posts');
    }
};
