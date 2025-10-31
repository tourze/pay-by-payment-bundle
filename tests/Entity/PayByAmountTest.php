<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;

/**
 * @internal
 */
#[CoversClass(PayByAmount::class)]
class PayByAmountTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $amount = new PayByAmount('100.50', 'USD');

        $this->assertEquals('100.50', $amount->getAmount());
        $this->assertEquals('USD', $amount->getCurrency());
    }

    public function testConstructorWithDefaultCurrency(): void
    {
        $amount = new PayByAmount('100.50');

        $this->assertEquals('100.50', $amount->getAmount());
        $this->assertEquals('AED', $amount->getCurrency());
    }

    public function testSetters(): void
    {
        $amount = new PayByAmount('100.50', 'USD');

        $amount->setAmount('200.75');
        $amount->setCurrency('EUR');

        $this->assertEquals('200.75', $amount->getAmount());
        $this->assertEquals('EUR', $amount->getCurrency());
    }

    public function testGetFormattedAmount(): void
    {
        $amount = new PayByAmount('100.50', 'USD');

        $this->assertEquals('100.50 USD', $amount->getFormattedAmount());
    }

    public function testToArray(): void
    {
        $amount = new PayByAmount('100.50', 'USD');

        $expected = [
            'currency' => 'USD',
            'amount' => '100.50',
        ];

        $this->assertEquals($expected, $amount->toArray());
    }

    public function testFromArray(): void
    {
        $data = [
            'amount' => '100.50',
            'currency' => 'USD',
        ];

        $amount = PayByAmount::fromArray($data);

        $this->assertEquals('100.50', $amount->getAmount());
        $this->assertEquals('USD', $amount->getCurrency());
    }

    public function testFromArrayWithDefaults(): void
    {
        $amount = PayByAmount::fromArray([]);

        $this->assertEquals('0.00', $amount->getAmount());
        $this->assertEquals('AED', $amount->getCurrency());
    }

    public function testEquals(): void
    {
        $amount1 = new PayByAmount('100.50', 'USD');
        $amount2 = new PayByAmount('100.50', 'USD');
        $amount3 = new PayByAmount('200.00', 'USD');
        $amount4 = new PayByAmount('100.50', 'EUR');

        $this->assertTrue($amount1->equals($amount2));
        $this->assertFalse($amount1->equals($amount3));
        $this->assertFalse($amount1->equals($amount4));
    }
}
