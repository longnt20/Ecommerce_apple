<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;
use Symfony\Component\HttpKernel\Exception\HttpException;

class VnPayGateway implements PaymentGateway
{
    public function assertConfigured(): void
    {
        if (! config('vnpay.enabled')) {
            throw new HttpException(503, 'Thanh toán trực tuyến chưa sẵn sàng.');
        }
        if (! config('vnpay.enabled')) {
            throw new HttpException(503, 'Thanh toán trực tuyến chưa sẵn sàng.');
        }
        foreach (['vnp_tmn_code', 'vnp_hash_secret', 'vnp_url', 'vnp_return_url'] as $key) {
            if (! is_string(config('vnpay.'.$key)) || config('vnpay.'.$key) === '') {
                throw new HttpException(503, 'Thanh toán trực tuyến chưa sẵn sàng.');
            }
        }
    }

    public function canonical(array $query): string
    {
        $data = array_filter($query, fn ($key) => str_starts_with($key, 'vnp_') && ! in_array($key, ['vnp_SecureHash', 'vnp_SecureHashType'], true), ARRAY_FILTER_USE_KEY);
        ksort($data, SORT_STRING);

        // VNPay PHP reference: urlencode(key/value), space encoded as '+'.
        return http_build_query($data, '', '&', PHP_QUERY_RFC1738);
    }

    public function verified(array $query): bool
    {
        $secret = config('vnpay.vnp_hash_secret');
        if (! is_string($secret) || $secret === '' || ! isset($query['vnp_SecureHash']) || ! is_string($query['vnp_SecureHash'])
            || ! preg_match('/^[a-fA-F0-9]{128}$/D', $query['vnp_SecureHash'])) {
            return false;
        }
        foreach ($query as $key => $value) {
            if (str_starts_with($key, 'vnp_') && ! is_string($value)) {
                return false;
            }
        }

        return hash_equals(hash_hmac('sha512', $this->canonical($query), $secret), strtolower($query['vnp_SecureHash']));
    }

    public function paymentUrl(PaymentAttempt $attempt, string $ip): string
    {
        $this->assertConfigured();
        abort_unless($attempt->merchant === config('vnpay.vnp_tmn_code') && $attempt->currency === 'VND', 409, 'Cấu hình thanh toán đã thay đổi.');
        $query = [
            'vnp_Version' => '2.1.0', 'vnp_Command' => 'pay',
            'vnp_TmnCode' => $attempt->merchant, 'vnp_TxnRef' => $attempt->reference,
            'vnp_Amount' => (string) ($attempt->amount_vnd * 100), 'vnp_CurrCode' => 'VND',
            'vnp_CreateDate' => $attempt->created_at->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_ExpireDate' => $attempt->expires_at->timezone('Asia/Ho_Chi_Minh')->format('YmdHis'),
            'vnp_IpAddr' => $ip, 'vnp_Locale' => 'vn', 'vnp_OrderType' => 'billpayment',
            'vnp_OrderInfo' => 'Thanh toan don hang #'.$attempt->order_id,
            'vnp_ReturnUrl' => config('vnpay.vnp_return_url'),
        ];
        $canonical = $this->canonical($query);

        return config('vnpay.vnp_url').'?'.$canonical.'&vnp_SecureHash='.hash_hmac('sha512', $canonical, config('vnpay.vnp_hash_secret'));
    }
}
