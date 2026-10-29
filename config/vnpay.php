<?php

return [
    // Enable only after the additive P2 migration and merchant IPN setup are reviewed.
    'enabled' => env('VNPAY_ENABLED', false),
    'vnp_tmn_code' => env('VNPAY_TMNCODE', ''),
    'vnp_hash_secret' => env('VNPAY_HASHSECRET', ''),
    'vnp_url' => env('VNPAY_URL', ''),
    'vnp_return_url' => env('VNPAY_RETURNURL', 'http://127.0.0.1:8000/vnpay/return'),
    'vnp_frontend_return_url' => env('VNPAY_FRONTEND_RETURNURL', 'http://localhost:3000/payment-success'),
];
