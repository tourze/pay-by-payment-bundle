<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PayByTransferType::class)]
class PayByTransferTypeTest extends AbstractEnumTestCase
{
    public function testGetDescription(): void
    {
        $this->assertEquals('PayBy系统内部账户间转账', PayByTransferType::INTERNAL->getDescription());
        $this->assertEquals('转账到外部银行账户', PayByTransferType::BANK_TRANSFER->getDescription());
        $this->assertEquals('转账到外部银行卡账户', PayByTransferType::TRANSFER_TO_BANK->getDescription());
        $this->assertEquals('转账到系统余额账户', PayByTransferType::TRANSFER_TO_BALANCE->getDescription());
        $this->assertEquals('转账到第三方支付平台', PayByTransferType::TRANSFER_TO_THIRD_PARTY->getDescription());
    }

    public function testGenOptions(): void
    {
        $options = array_column(PayByTransferType::genOptions(), 'label', 'value');

        $this->assertCount(5, $options);
        $this->assertEquals('内部转账', $options['INTERNAL']);
        $this->assertEquals('银行转账', $options['BANK_TRANSFER']);
        $this->assertEquals('转账到银行卡', $options['TRANSFER_TO_BANK']);
        $this->assertEquals('转账到余额', $options['TRANSFER_TO_BALANCE']);
        $this->assertEquals('转账到第三方', $options['TRANSFER_TO_THIRD_PARTY']);
    }

    public function testToArray(): void
    {
        $result = PayByTransferType::INTERNAL->toArray();

        $expected = [
            'value' => 'INTERNAL',
            'label' => '内部转账',
        ];
        $this->assertEquals($expected, $result);
    }
}
