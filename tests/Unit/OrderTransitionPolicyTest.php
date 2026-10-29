<?php

namespace Tests\Unit;

use App\Exceptions\OrderTransitionConflict;
use App\Services\Ordering\OrderTransitionPolicy;
use PHPUnit\Framework\TestCase;

class OrderTransitionPolicyTest extends TestCase
{
    public function test_one_acyclic_transition_policy_and_terminal_states(): void
    {
        $policy = new OrderTransitionPolicy;
        $this->assertSame(['confirmed', 'cancelled'], $policy->next('pending'));
        $this->assertSame(['shipping'], $policy->next('confirmed'));
        $this->assertSame(['completed'], $policy->next('shipping'));
        $this->assertSame([], $policy->next('completed'));
        $this->assertSame([], $policy->next('cancelled'));
        $this->assertSame([], $policy->next('unknown'));
    }

    /** @dataProvider invalidTransitions */
    public function test_forbidden_transitions_are_domain_conflicts(string $from, string $to): void
    {
        $this->expectException(OrderTransitionConflict::class);
        (new OrderTransitionPolicy)->assertAllowed($from, $to);
    }

    public static function invalidTransitions(): array
    {
        return [['pending', 'completed'], ['pending', 'shipping'], ['confirmed', 'cancelled'], ['shipping', 'confirmed'], ['completed', 'cancelled'], ['cancelled', 'pending']];
    }
}
