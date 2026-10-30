<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'idempotency_key' => 'required|uuid',
            'coupon_code' => 'nullable|string|max:255',
            'payment_method' => 'required|in:cod,vnpay',
            'mode' => 'sometimes|in:cart,buy-now',
            'items' => 'required|array|min:1|max:100',
            'items.*.product_id' => 'required|integer|min:1',
            'items.*.variant_id' => 'required|integer|min:1|distinct',
            'items.*.quantity' => 'required|integer|min:1|max:10000',
            // Legacy clients may send price. It is deliberately never used.
            'fullname' => 'required|string|max:255',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'required|string|max:255',
            'ward' => 'required|string|max:255',
            'district' => 'required|string|max:255',
            'province' => 'required|string|max:255',
            'note' => 'nullable|string|max:500',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->header('Idempotency-Key') !== null) {
            if ($this->has('idempotency_key') && $this->input('idempotency_key') !== $this->header('Idempotency-Key')) {
                throw \Illuminate\Validation\ValidationException::withMessages(['idempotency_key' => 'Header và body operation key không khớp.']);
            }
            $this->merge(['idempotency_key' => $this->header('Idempotency-Key')]);
        }
    }
}
