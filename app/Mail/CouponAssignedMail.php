<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
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
        return $this->subject('Mã Giảm Giá Đặc Biệt từ CourseMeLy')
            ->view('emails.coupon')
            ->with([
                'user' => $this->user,
                'discount' => $this->discount
            ]);
    }
}
