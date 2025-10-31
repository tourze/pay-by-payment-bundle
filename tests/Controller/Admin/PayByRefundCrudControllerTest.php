<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Controller\Admin;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PayByPaymentBundle\Controller\Admin\PayByRefundCrudController;
use Tourze\PayByPaymentBundle\Entity\PayByRefund;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PayByRefundCrudController::class)]
#[RunTestsInSeparateProcesses]
class PayByRefundCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    private PayByRefundCrudController $controller;

    protected function onSetUp(): void
    {
        $this->controller = new PayByRefundCrudController();
    }

    protected function getControllerService(): PayByRefundCrudController
    {
        return $this->controller;
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
