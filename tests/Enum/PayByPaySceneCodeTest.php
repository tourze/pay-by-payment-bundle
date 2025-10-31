<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Enum;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(PayByPaySceneCode::class)]
class PayByPaySceneCodeTest extends AbstractEnumTestCase
{
    public function testGetDescription(): void
    {
        $this->assertEquals('生成动态二维码，用户扫码支付', PayByPaySceneCode::DYNQR->getDescription());
        $this->assertEquals('集成支付网关，支持多种支付方式', PayByPaySceneCode::ONLINE->getDescription());
        $this->assertEquals('线下实体店支付', PayByPaySceneCode::IN_STORE->getDescription());
        $this->assertEquals('移动应用内支付', PayByPaySceneCode::MOBILE->getDescription());
        $this->assertEquals('浏览器内网页支付', PayByPaySceneCode::WEB->getDescription());
        $this->assertEquals('微信小程序内支付', PayByPaySceneCode::MINI_PROGRAM->getDescription());
        $this->assertEquals('手机应用内支付', PayByPaySceneCode::APP->getDescription());
        $this->assertEquals('手机H5页面支付', PayByPaySceneCode::H5->getDescription());
    }

    public function testGenOptions(): void
    {
        $options = array_column(PayByPaySceneCode::genOptions(), 'label', 'value');

        $this->assertCount(9, $options);
        $this->assertEquals('动态二维码支付', $options['DYNQR']);
        $this->assertEquals('在线支付', $options['ONLINE']);
        $this->assertEquals('店内支付', $options['IN_STORE']);
        $this->assertEquals('移动支付', $options['MOBILE']);
        $this->assertEquals('网页支付', $options['WEB']);
        $this->assertEquals('小程序支付', $options['MINI_PROGRAM']);
        $this->assertEquals('APP支付', $options['APP']);
        $this->assertEquals('H5支付', $options['H5']);
        $this->assertEquals('支付页面', $options['PAYPAGE']);
    }

    public function testToArray(): void
    {
        $result = PayByPaySceneCode::DYNQR->toArray();

        $expected = [
            'value' => 'DYNQR',
            'label' => '动态二维码支付',
        ];
        $this->assertEquals($expected, $result);
    }
}
