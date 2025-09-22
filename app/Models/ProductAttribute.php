<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductAttribute extends Model
{
    protected $fillable = [
        'type',
        'value',
        'label',
        'hex_code',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    // Constants
    const TYPE_COLOR = 'color';
    const TYPE_STORAGE = 'storage';

    public static function getTypes()
    {
        return [
            self::TYPE_COLOR => 'Màu sắc',
            self::TYPE_STORAGE => 'Dung lượng',
        ];
    }
}
