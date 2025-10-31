<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PayByOrderStatus::class)]
class PayByOrderStatusTest extends AbstractEnumTestCase
{
    public function testGetColor(): void
    {
        $this->assertEquals('warning', PayByOrderStatus::PENDING->getColor());
        $this->assertEquals('info', PayByOrderStatus::PROCESSING->getColor());
        $this->assertEquals('success', PayByOrderStatus::SUCCESS->getColor());
        $this->assertEquals('danger', PayByOrderStatus::FAILED->getColor());
        $this->assertEquals('secondary', PayByOrderStatus::CANCELLED->getColor());
        $this->assertEquals('dark', PayByOrderStatus::TIMEOUT->getColor());
        $this->assertEquals('secondary', PayByOrderStatus::REFUNDED->getColor());
    }

    public function testIsFinal(): void
    {
        $this->assertFalse(PayByOrderStatus::PENDING->isFinal());
        $this->assertFalse(PayByOrderStatus::PROCESSING->isFinal());
        $this->assertTrue(PayByOrderStatus::SUCCESS->isFinal());
        $this->assertTrue(PayByOrderStatus::FAILED->isFinal());
        $this->assertTrue(PayByOrderStatus::CANCELLED->isFinal());
        $this->assertTrue(PayByOrderStatus::TIMEOUT->isFinal());
        $this->assertTrue(PayByOrderStatus::REFUNDED->isFinal());
    }

    public function testCanBeCancelled(): void
    {
        $this->assertTrue(PayByOrderStatus::PENDING->canBeCancelled());
        $this->assertTrue(PayByOrderStatus::PROCESSING->canBeCancelled());
        $this->assertFalse(PayByOrderStatus::SUCCESS->canBeCancelled());
        $this->assertFalse(PayByOrderStatus::FAILED->canBeCancelled());
        $this->assertFalse(PayByOrderStatus::CANCELLED->canBeCancelled());
        $this->assertFalse(PayByOrderStatus::TIMEOUT->canBeCancelled());
        $this->assertFalse(PayByOrderStatus::REFUNDED->canBeCancelled());
    }

    public function testGenOptions(): void
    {
        $options = array_column(PayByOrderStatus::genOptions(), 'label', 'value');

        $this->assertCount(7, $options);
        $this->assertEquals('待支付', $options['PENDING']);
        $this->assertEquals('处理中', $options['PROCESSING']);
        $this->assertEquals('支付成功', $options['SUCCESS']);
        $this->assertEquals('支付失败', $options['FAILED']);
        $this->assertEquals('已取消', $options['CANCELLED']);
        $this->assertEquals('超时', $options['TIMEOUT']);
        $this->assertEquals('已退款', $options['REFUNDED']);
    }

    public function testToArray(): void
    {
        $result = PayByOrderStatus::PENDING->toArray();

        $expected = [
            'value' => 'PENDING',
            'label' => '待支付',
        ];
        $this->assertEquals($expected, $result);
    }
}
