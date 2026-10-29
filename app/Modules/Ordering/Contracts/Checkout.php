<?php

namespace App\Modules\Ordering\Contracts;

use App\SharedKernel\ActorContext;

interface Checkout
{
    public function place(array $input, ActorContext $actor): OrderSnapshot;
}
