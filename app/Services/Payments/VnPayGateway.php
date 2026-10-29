<?php

namespace App\Services\Payments;

use App\Models\PaymentAttempt;
use App\Modules\Payments\Contracts\Attempt;

/** @deprecated Gateway signature adapter; cryptographic implementation exists only in Payments module. */
class VnPayGateway extends \App\Modules\Payments\Infrastructure\VnPayGateway implements PaymentGateway
{
    public function paymentUrl(Attempt|PaymentAttempt $attempt, string $ip): string
    {
        if ($attempt instanceof PaymentAttempt) {
            $attempt = new Attempt($attempt->id, $attempt->order_id, $attempt->reference,
                $attempt->merchant, $attempt->amount_vnd, $attempt->currency, $attempt->status,
                $attempt->created_at->toDateTimeImmutable(), $attempt->expires_at->toDateTimeImmutable());
        }

        return parent::paymentUrl($attempt, $ip);
    }
}
