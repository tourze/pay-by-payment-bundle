<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Request;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\TestCase;
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Request\CreateOrderRequest;

/**
 * @internal
 */
#[CoversClass(CreateOrderRequest::class)]
final class CreateOrderRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // 此测试类不需要数据库，只测试 DTO 的基本功能
    }

    public function testConstructorAndBasicGetters(): void
    {
        $merchantOrderNo = 'TEST-ORDER-123';
        $subject = 'Test Payment Order';
        $totalAmount = new PayByAmount('100.50', 'USD');
        $paySceneCode = PayByPaySceneCode::DYNQR;

        $request = new CreateOrderRequest($merchantOrderNo, $subject, $totalAmount, $paySceneCode);

        $this->assertEquals($merchantOrderNo, $request->getMerchantOrderNo());
        $this->assertEquals($subject, $request->getSubject());
        $this->assertEquals($totalAmount, $request->getTotalAmount());
        $this->assertEquals($paySceneCode, $request->getPaySceneCode());
        $this->assertNull($request->getNotifyUrl());
        $this->assertNull($request->getReturnUrl());
        $this->assertNull($request->getAccessoryContent());
    }

    public function testSettersAndGetters(): void
    {
        $request = new CreateOrderRequest(
            'ORDER-123',
            'Test Subject',
            new PayByAmount('50.00', 'AED'),
            PayByPaySceneCode::ONLINE
        );

        $newMerchantOrderNo = 'NEW-ORDER-456';
        $newSubject = 'Updated Subject';
        $newAmount = new PayByAmount('200.75', 'EUR');
        $newPaySceneCode = PayByPaySceneCode::IN_STORE;
        $notifyUrl = 'https://example.com/notify';
        $returnUrl = 'https://example.com/return';
        $accessoryContent = ['key1' => 'value1', 'key2' => 'value2'];

        $request->setMerchantOrderNo($newMerchantOrderNo);
        $request->setSubject($newSubject);
        $request->setTotalAmount($newAmount);
        $request->setPaySceneCode($newPaySceneCode);
        $request->setNotifyUrl($notifyUrl);
        $request->setReturnUrl($returnUrl);
        $request->setAccessoryContent($accessoryContent);

        $this->assertEquals($newMerchantOrderNo, $request->getMerchantOrderNo());
        $this->assertEquals($newSubject, $request->getSubject());
        $this->assertEquals($newAmount, $request->getTotalAmount());
        $this->assertEquals($newPaySceneCode, $request->getPaySceneCode());
        $this->assertEquals($notifyUrl, $request->getNotifyUrl());
        $this->assertEquals($returnUrl, $request->getReturnUrl());
        $this->assertEquals($accessoryContent, $request->getAccessoryContent());
    }

    public function testToArrayWithRequiredFieldsOnly(): void
    {
        $merchantOrderNo = 'ORDER-789';
        $subject = 'Minimal Order';
        $totalAmount = new PayByAmount('25.99', 'GBP');
        $paySceneCode = PayByPaySceneCode::MOBILE;

        $request = new CreateOrderRequest($merchantOrderNo, $subject, $totalAmount, $paySceneCode);

        $expected = [
            'merchantOrderNo' => $merchantOrderNo,
            'subject' => $subject,
            'totalAmount' => $totalAmount->toArray(),
            'paySceneCode' => $paySceneCode->value,
        ];

        $this->assertEquals($expected, $request->toArray());
    }

    public function testToArrayWithAllFields(): void
    {
        $merchantOrderNo = 'FULL-ORDER-123';
        $subject = 'Complete Order';
        $totalAmount = new PayByAmount('99.99', 'USD');
        $paySceneCode = PayByPaySceneCode::DYNQR;
        $notifyUrl = 'https://api.example.com/webhook';
        $returnUrl = 'https://shop.example.com/success';
        $accessoryContent = [
            'customerId' => 'CUST-456',
            'metadata' => ['source' => 'web', 'campaign' => 'summer2023'],
        ];

        $request = new CreateOrderRequest($merchantOrderNo, $subject, $totalAmount, $paySceneCode);
        $request->setNotifyUrl($notifyUrl);
        $request->setReturnUrl($returnUrl);
        $request->setAccessoryContent($accessoryContent);

        $expected = [
            'merchantOrderNo' => $merchantOrderNo,
            'subject' => $subject,
            'totalAmount' => $totalAmount->toArray(),
            'paySceneCode' => $paySceneCode->value,
            'notifyUrl' => $notifyUrl,
            'returnUrl' => $returnUrl,
            'accessoryContent' => $accessoryContent,
        ];

        $this->assertEquals($expected, $request->toArray());
    }

    public function testToArrayWithNullOptionalFields(): void
    {
        $request = new CreateOrderRequest(
            'NULL-TEST-ORDER',
            'Null Test Subject',
            new PayByAmount('10.00', 'AED'),
            PayByPaySceneCode::ONLINE
        );

        $request->setNotifyUrl(null);
        $request->setReturnUrl(null);
        $request->setAccessoryContent(null);

        $array = $request->toArray();

        $this->assertArrayNotHasKey('notifyUrl', $array);
        $this->assertArrayNotHasKey('returnUrl', $array);
        $this->assertArrayNotHasKey('accessoryContent', $array);
    }

    public function testToArrayWithEmptyAccessoryContent(): void
    {
        $request = new CreateOrderRequest(
            'EMPTY-ACCESS-ORDER',
            'Empty Accessory Test',
            new PayByAmount('15.50', 'USD'),
            PayByPaySceneCode::IN_STORE
        );

        $request->setAccessoryContent([]);

        $array = $request->toArray();

        $this->assertArrayHasKey('accessoryContent', $array);
        $this->assertEquals([], $array['accessoryContent']);
    }
}
