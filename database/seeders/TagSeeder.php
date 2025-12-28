<?php

namespace Database\Seeders;

use App\Models\Tag;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    public function run(): void
    {
        $styleTags = [
            'Điện thoại chơi game',
            'Điện thoại pin trâu',
            'Điện thoại chụp ảnh đẹp',
            'Điện thoại 5G',
            'Điện thoại cũ',
            'Điện thoại vừa tay'
        ];


        foreach ($styleTags as $tag) {
            Tag::create([
                'name' => $tag,
                'slug' => Str::slug($tag) . '-' . Str::random(8),
            ]);
        }
    }
}
