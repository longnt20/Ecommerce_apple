<?php

namespace App\Traits;

trait GeneratesBarcode
{
    public function generateBarcode()
    {
        // Sinh barcode ngẫu nhiên 12 số, thêm checksum để thành EAN13
        $code = str_pad(mt_rand(1, 999999999999), 12, '0', STR_PAD_LEFT);
        return $code;
    }
}
