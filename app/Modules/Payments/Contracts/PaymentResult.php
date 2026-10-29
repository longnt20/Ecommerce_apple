<?php

namespace App\Modules\Payments\Contracts;

final readonly class PaymentResult
{
    public function __construct(public string $code, public ?string $outcome = null, public ?string $providerTransaction = null) {}

    public function response(): array
    {
        return ['RspCode' => $this->code, 'Message' => match ($this->code) {
            '00' => 'Confirm Success', '02' => 'Order already confirmed', '01' => 'Order not found',
            '04' => 'Invalid amount', '97' => 'Invalid signature', default => 'Unable to confirm',
        }];
    }
}
