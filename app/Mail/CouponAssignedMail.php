<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class CouponAssignedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $user;

    protected $discount;

    public function __construct($user, $discount)
    {
        $this->user = $user;
        $this->discount = $discount;
    }

    public function build()
    {
        return $this->subject('Coupon của bạn tại Apple Store')
            ->view('emails.coupon')
            ->with([
                'user' => $this->user,
                'discount' => $this->discount,
            ]);
    }
}
