<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PayByTransferStatus::class)]
class PayByTransferStatusTest extends AbstractEnumTestCase
{
    public function testGetColor(): void
    {
        $this->assertEquals('warning', PayByTransferStatus::PENDING->getColor());
        $this->assertEquals('info', PayByTransferStatus::PROCESSING->getColor());
        $this->assertEquals('success', PayByTransferStatus::SUCCESS->getColor());
        $this->assertEquals('danger', PayByTransferStatus::FAILED->getColor());
        $this->assertEquals('secondary', PayByTransferStatus::CANCELLED->getColor());
    }

    public function testIsFinal(): void
    {
        $this->assertFalse(PayByTransferStatus::PENDING->isFinal());
        $this->assertFalse(PayByTransferStatus::PROCESSING->isFinal());
        $this->assertTrue(PayByTransferStatus::SUCCESS->isFinal());
        $this->assertTrue(PayByTransferStatus::FAILED->isFinal());
        $this->assertTrue(PayByTransferStatus::CANCELLED->isFinal());
    }

    public function testGenOptions(): void
    {
        $options = array_column(PayByTransferStatus::genOptions(), 'label', 'value');

        $this->assertCount(5, $options);
        $this->assertEquals('待处理', $options['PENDING']);
        $this->assertEquals('处理中', $options['PROCESSING']);
        $this->assertEquals('转账成功', $options['SUCCESS']);
        $this->assertEquals('转账失败', $options['FAILED']);
        $this->assertEquals('已取消', $options['CANCELLED']);
    }

    public function testToArray(): void
    {
        $result = PayByTransferStatus::PENDING->toArray();

        $expected = [
            'value' => 'PENDING',
            'label' => '待处理',
        ];
        $this->assertEquals($expected, $result);
    }
}
