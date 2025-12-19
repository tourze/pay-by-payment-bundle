<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Controller\Admin;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PayByPaymentBundle\Controller\Admin\PayByRefundCrudController;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PayByRefundCrudController::class)]
#[RunTestsInSeparateProcesses]
class PayByRefundCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function afterEasyAdminSetUp(): void
    {
        // 确保测试数据存在
        $container = self::getContainer();
        /** @var ObjectManager $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // 检查是否已有退款数据
        $refundRepository = $entityManager->getRepository(PayByRefund::class);
        $existingRefunds = method_exists($refundRepository, 'count') ? $refundRepository->count() : count($refundRepository->findAll());
        if (0 === $existingRefunds) {
            // 先创建配置
            $configRepository = $entityManager->getRepository(PayByConfig::class);
            $existingConfigs = method_exists($configRepository, 'count') ? $configRepository->count() : count($configRepository->findAll());
            if (0 === $existingConfigs) {
                $config = new PayByConfig();
                $config->setName('test_config');
                $config->setDescription('Test payment configuration');
                $config->setApiBaseUrl('https://api.test.payby.com');
                $config->setApiKey('test_api_key');
                $config->setApiSecret('test_api_secret');
                $config->setMerchantId('test_merchant_id');
                $config->setEnabled(true);
                $config->setDefault(true);

                $entityManager->persist($config);
                $entityManager->flush();
            } else {
                $config = $configRepository->findOneBy([]);
            }

            // 创建订单
            $orderRepository = $entityManager->getRepository(PayByOrder::class);
            $existingOrders = method_exists($orderRepository, 'count') ? $orderRepository->count() : count($orderRepository->findAll());
            if (0 === $existingOrders) {
                $order = new PayByOrder();
                $order->setOrderId('TEST_ORDER_001');
                $order->setMerchantOrderNo('MERCHANT_001');
                $order->setSubject('Test Order');
                $order->setBody('Test order description');
                $order->setAmount('100.00');
                $order->setCurrency('AED');
                $order->setPaySceneCode(PayByPaySceneCode::DYNQR);
                $order->setStatus(PayByOrderStatus::SUCCESS);
                $order->setConfig($config);

                $entityManager->persist($order);
                $entityManager->flush();
            } else {
                $order = $orderRepository->findOneBy([]);
            }

            // 创建退款记录
            $refund = new PayByRefund();
            $refund->setRefundId('TEST_REFUND_001');
            $refund->setMerchantRefundNo('MERCHANT_REFUND_001');
            $refund->setOrder($order);
            $refund->setRefundAmount(new PayByAmount('50.00', 'AED'));
            $refund->setStatus(PayByRefundStatus::PENDING);
            $refund->setRefundReason('Test refund reason');

            $entityManager->persist($refund);
            $entityManager->flush();
        }
    }

    protected function getControllerService(): PayByRefundCrudController
    {
        return new PayByRefundCrudController();
    }

    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield 'Order' => ['关联订单'];
        yield 'Refund ID' => ['PayBy退款ID'];
        yield 'Merchant Refund No' => ['商户退款号'];
        yield 'Refund Amount' => ['退款金额'];
        yield 'Currency' => ['货币类型'];
        yield 'Status' => ['退款状态'];
        yield 'Refund Reason' => ['退款原因'];
        yield 'Notify Url' => ['通知地址'];
        yield 'Refund Time' => ['退款时间'];
        yield 'Created Time' => ['创建时间'];
        yield 'Updated Time' => ['更新时间'];
    }

    public function testEntityFqcn(): void
    {
        self::assertSame(PayByRefund::class, PayByRefundCrudController::getEntityFqcn());
    }

    public static function provideNewPageFields(): iterable
    {
        yield 'Merchant Refund No' => ['merchantRefundNo'];
        yield 'Status' => ['status'];
        yield 'Refund Reason' => ['refundReason'];
    }

    public static function provideEditPageFields(): iterable
    {
        yield 'Order' => ['order'];
        yield 'Merchant Refund No' => ['merchantRefundNo'];
        yield 'Status' => ['status'];
        yield 'Refund Reason' => ['refundReason'];
    }

    public function testValidationErrors(): void
    {
        $validator = self::getService(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $entity = new PayByRefund();
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, $violations->count());

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        self::assertContains('This value should not be blank.', $violationMessages);
    }
}
