<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;

class PayByRefundFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 在测试环境中不加载Fixtures，避免干扰单元测试
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        if ('test' === $env) {
            return;
        }

        $order = new PayByOrder();
        $order->setOrderId('test_order_refund_001');
        $order->setMerchantOrderNo('merchant_order_refund_001');
        $order->setTotalAmount(new PayByAmount('100.00', 'CNY'));
        $order->setSubject('测试退款订单');
        $order->setPaySceneCode(PayByPaySceneCode::ONLINE);
        $order->setStatus(PayByOrderStatus::SUCCESS);

        $refund = new PayByRefund();
        $refund->setRefundId('test_refund_001');
        $refund->setMerchantRefundNo('merchant_refund_001');
        $refund->setOrder($order);
        $refund->setRefundAmount(new PayByAmount('50.00', 'CNY'));
        $refund->setRefundReason('测试退款');
        $refund->setStatus(PayByRefundStatus::SUCCESS);
        $refund->setRefundTime(new \DateTimeImmutable());

        $manager->persist($order);
        $manager->persist($refund);
        $manager->flush();
    }
}
