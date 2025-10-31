<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PayByRefundStatus::class)]
class PayByRefundStatusTest extends AbstractEnumTestCase
{
    public function testGetColor(): void
    {
        $this->assertEquals('warning', PayByRefundStatus::PENDING->getColor());
        $this->assertEquals('info', PayByRefundStatus::PROCESSING->getColor());
        $this->assertEquals('success', PayByRefundStatus::SUCCESS->getColor());
        $this->assertEquals('danger', PayByRefundStatus::FAILED->getColor());
        $this->assertEquals('secondary', PayByRefundStatus::CANCELLED->getColor());
        $this->assertEquals('dark', PayByRefundStatus::UNKNOWN->getColor());
    }

    public function testIsFinal(): void
    {
        $this->assertFalse(PayByRefundStatus::PENDING->isFinal());
        $this->assertFalse(PayByRefundStatus::PROCESSING->isFinal());
        $this->assertTrue(PayByRefundStatus::SUCCESS->isFinal());
        $this->assertTrue(PayByRefundStatus::FAILED->isFinal());
        $this->assertTrue(PayByRefundStatus::CANCELLED->isFinal());
        $this->assertTrue(PayByRefundStatus::UNKNOWN->isFinal());
    }

    public function testGenOptions(): void
    {
        $options = array_column(PayByRefundStatus::genOptions(), 'label', 'value');

        $this->assertCount(6, $options);
        $this->assertEquals('待处理', $options['PENDING']);
        $this->assertEquals('处理中', $options['PROCESSING']);
        $this->assertEquals('退款成功', $options['SUCCESS']);
        $this->assertEquals('退款失败', $options['FAILED']);
        $this->assertEquals('已取消', $options['CANCELLED']);
        $this->assertEquals('未知状态', $options['UNKNOWN']);
    }

    public function testToArray(): void
    {
        $result = PayByRefundStatus::PENDING->toArray();

        $expected = [
            'value' => 'PENDING',
            'label' => '待处理',
        ];
        $this->assertEquals($expected, $result);
    }
}
