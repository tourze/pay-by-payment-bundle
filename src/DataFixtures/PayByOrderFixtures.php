<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;

class PayByOrderFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 在测试环境中不加载Fixtures，避免干扰单元测试
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        if ('test' === $env) {
            return;
        }

        $order = new PayByOrder();
        $order->setOrderId('test_order_001');
        $order->setMerchantOrderNo('merchant_order_001');
        $order->setTotalAmount(new PayByAmount('100.00', 'CNY'));
        $order->setSubject('测试订单');
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::SUCCESS);
        $order->setPayTime(new \DateTimeImmutable());

        $manager->persist($order);
        $manager->flush();
    }
}
