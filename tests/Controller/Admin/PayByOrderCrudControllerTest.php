<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PayByPaymentBundle\Controller\Admin\PayByOrderCrudController;
use Tourze\PayByPaymentBundle\Entity\PayByOrder;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PayByOrderCrudController::class)]
#[RunTestsInSeparateProcesses]
class PayByOrderCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private PayByOrderCrudController $controller;

    protected function onSetUp(): void
    {
        $this->controller = new PayByOrderCrudController();
    }

    protected function getControllerService(): PayByOrderCrudController
    {
        return $this->controller;
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '支付配置' => ['支付配置'];
        yield 'PayBy订单ID' => ['PayBy订单ID'];
        yield '商户订单号' => ['商户订单号'];
        yield '订单金额' => ['订单金额'];
        yield '订单标题' => ['订单标题'];
        yield '订单描述' => ['订单描述'];
        yield '支付方式' => ['支付方式'];
        yield '支付场景' => ['支付场景'];
        yield '订单状态' => ['订单状态'];
        yield '二维码链接' => ['二维码链接'];
        yield '支付链接' => ['支付链接'];
        yield '通知地址' => ['通知地址'];
        yield '返回地址' => ['返回地址'];
        yield '支付时间' => ['支付时间'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'config' => ['config'];
        yield 'orderId' => ['orderId'];
        yield 'merchantOrderNo' => ['merchantOrderNo'];
        yield 'amount' => ['amount'];
        yield 'currency' => ['currency'];
        yield 'subject' => ['subject'];
        yield 'body' => ['body'];
        yield 'paymentMethod' => ['paymentMethod'];
        yield 'paySceneCode' => ['paySceneCode'];
        yield 'status' => ['status'];
        yield 'qrCodeData' => ['qrCodeData'];
        yield 'qrCodeUrl' => ['qrCodeUrl'];
        yield 'paymentUrl' => ['paymentUrl'];
        yield 'notifyUrl' => ['notifyUrl'];
        yield 'returnUrl' => ['returnUrl'];
        yield 'accessoryContent' => ['accessoryContent'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'config' => ['config'];
        yield 'orderId' => ['orderId'];
        yield 'merchantOrderNo' => ['merchantOrderNo'];
        yield 'amount' => ['amount'];
        yield 'currency' => ['currency'];
        yield 'subject' => ['subject'];
        yield 'body' => ['body'];
        yield 'paymentMethod' => ['paymentMethod'];
        yield 'paySceneCode' => ['paySceneCode'];
        yield 'status' => ['status'];
        yield 'qrCodeData' => ['qrCodeData'];
        yield 'qrCodeUrl' => ['qrCodeUrl'];
        yield 'paymentUrl' => ['paymentUrl'];
        yield 'notifyUrl' => ['notifyUrl'];
        yield 'returnUrl' => ['returnUrl'];
        yield 'accessoryContent' => ['accessoryContent'];
    }

    public function testEntityFqcn(): void
    {
        self::assertSame(PayByOrder::class, PayByOrderCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        /** @var ValidatorInterface $validator */
        $validator = self::getService(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $entity = new PayByOrder();
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, $violations->count());

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        self::assertContains('This value should not be blank.', $violationMessages);
    }
}
