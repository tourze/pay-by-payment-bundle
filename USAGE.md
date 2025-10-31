# PayBy Payment Bundle 使用示例

## 安装

```bash
composer require tourze/pay-by-payment-bundle
```

## 配置

在 `config/packages/pay_by_payment.yaml` 中配置：

```yaml
pay_by_payment:
    api_base_url: 'https://api.payby.com'
    api_version: 'v1'
    private_key: '%env(PAYBY_PRIVATE_KEY)%'
    public_key: '%env(PAYBY_PUBLIC_KEY)%'
    timeout: 30
    debug: false
```

在 `.env` 文件中添加：

```env
PAYBY_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----\n...\n-----END RSA PRIVATE KEY-----"
PAYBY_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\n...\n-----END PUBLIC KEY-----"
```

## 基本使用

### 创建订单

```php
use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Service\PayByOrderService;

// 创建金额对象
$amount = new PayByAmount('100.00', 'AED');

// 创建订单
$order = $orderService->createOrder(
    merchantOrderNo: 'ORDER_' . time(),
    subject: '测试商品',
    totalAmount: $amount,
    paySceneCode: PayByPaySceneCode::DYNQR,
    notifyUrl: 'https://your-site.com/api/pay-by/notification',
    returnUrl: 'https://your-site.com/payment/result'
);

// 获取支付信息
$qrCodeData = $order->getQrCodeData();
$paymentUrl = $order->getPaymentUrl();
```

### 处理支付通知

```php
// 在控制器中处理通知
#[Route('/api/pay-by/notification', name: 'pay_by_notification')]
public function handleNotification(Request $request): JsonResponse
{
    $data = json_decode($request->getContent(), true);
    $signature = $request->headers->get('X-PayBy-Signature');
    
    // 验证签名并处理通知
    $result = $this->orderService->handlePaymentNotification($data);
    
    return $result 
        ? new JsonResponse(['status' => 'success'])
        : new JsonResponse(['status' => 'error'], 400);
}
```

### 创建退款

```php
use Tourze\PayByPaymentBundle\Service\PayByRefundService;

$refundAmount = new PayByAmount('50.00', 'AED');

$refund = $refundService->createRefund(
    merchantRefundNo: 'REFUND_' . time(),
    merchantOrderNo: 'ORDER_123',
    refundAmount: $refundAmount,
    refundReason: '客户取消订单',
    notifyUrl: 'https://your-site.com/api/pay-by/refund-notification'
);
```

### 创建转账

```php
use Tourze\PayByPaymentBundle\Service\PayByTransferService;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;

$transferAmount = new PayByAmount('1000.00', 'AED');

// 内部转账
$transfer = $transferService->createInternalTransfer(
    merchantTransferNo: 'TRANSFER_' . time(),
    fromAccount: 'SENDER_ACCOUNT_ID',
    toAccount: 'RECEIVER_ACCOUNT_ID',
    transferAmount: $transferAmount,
    transferReason: '业务往来',
    notifyUrl: 'https://your-site.com/api/pay-by/transfer-notification'
);

// 银行转账
$bankTransfer = $transferService->createBankTransfer(
    merchantTransferNo: 'BANK_TRANSFER_' . time(),
    fromAccount: 'SENDER_ACCOUNT_ID',
    bankTransferInfo: [
        'bankCode' => 'ENBD',
        'accountNumber' => '1234567890',
        'accountName' => '收款人姓名',
        'swiftCode' => 'EBILAEAD',
        'iban' => 'AE123456789012345678901'
    ],
    transferAmount: $transferAmount,
    transferReason: '投资理财',
    notifyUrl: 'https://your-site.com/api/pay-by/bank-transfer-notification'
);
```

## 查询操作

```php
// 查询订单
$order = $orderService->queryOrder('PAYBY_ORDER_123');

// 查询退款
$refund = $refundService->queryRefund('PAYBY_REFUND_123');

// 查询转账
$transfer = $transferService->queryTransfer('PAYBY_TRANSFER_123');
```

## 数据库操作

### 使用仓库查询

```php
use Tourze\PayByPaymentBundle\Repository\PayByOrderRepository;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;

// 通过商户订单号查找
$order = $orderRepository->findByMerchantOrderNo('ORDER_123');

// 查找待支付订单
$pendingOrders = $orderRepository->findPendingOrders();

// 查找已支付订单
$paidOrders = $orderRepository->findPaidOrders();

// 按日期范围查询
$orders = $orderRepository->findOrdersByDateRange(
    new \DateTime('2024-01-01'),
    new \DateTime('2024-01-31')
);

// 获取统计信息
$statistics = $orderRepository->getOrderStatistics();
```

## 实体使用

### 订单实体

```php
// 检查订单状态
if ($order->isPaid()) {
    // 订单已支付
}

// 检查是否可以取消
if ($order->canBeCancelled()) {
    // 可以取消订单
}

// 获取可退款金额
$refundableAmount = $order->getRefundableAmount();

// 获取格式化金额
$formattedAmount = $order->getAmountFormatted();
```

### 金额实体

```php
use Tourze\PayByPaymentBundle\Entity\PayByAmount;

$amount = new PayByAmount('100.50', 'AED');

// 获取金额信息
echo $amount->getAmount(); // '100.50'
echo $amount->getCurrency(); // 'AED'
echo $amount->getFormattedAmount(); // '100.50 AED'

// 转换为数组
$array = $amount->toArray();
// ['currency' => 'AED', 'amount' => '100.50']

// 从数组创建
$amount2 = PayByAmount::fromArray(['amount' => '200.00', 'currency' => 'USD']);
```

## 枚举使用

```php
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;

// 订单状态
$status = PayByOrderStatus::SUCCESS;
echo $status->getLabel(); // '支付成功'
echo $status->getColor(); // 'success'
echo $status->isFinal(); // true

// 支付场景
$scene = PayByPaySceneCode::DYNQR;
echo $scene->getLabel(); // '动态二维码支付'
echo $scene->getDescription(); // '生成动态二维码，用户扫码支付'
```

## 错误处理

```php
use Tourze\PayByPaymentBundle\Exception\PayByApiException;

try {
    $order = $orderService->createOrder(/* ... */);
} catch (PayByApiException $e) {
    // API 调用失败
    echo 'Error: ' . $e->getMessage();
    echo 'Error Code: ' . $e->getErrorCode();
    echo 'Response Data: ' . json_encode($e->getResponseData());
} catch (\InvalidArgumentException $e) {
    // 参数错误
    echo 'Invalid argument: ' . $e->getMessage();
}
```

## 测试

运行测试：

```bash
# 运行所有测试
./vendor/bin/phpunit packages/pay-by-payment-bundle/tests/

# 运行特定测试
./vendor/bin/phpunit packages/pay-by-payment-bundle/tests/Service/PayByOrderServiceTest.php

# 运行静态分析
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/pay-by-payment-bundle/src --level=8
```

## 日志

Bundle 会记录详细的操作日志，包括：

- API 请求和响应
- 签名生成和验证
- 订单状态变更
- 错误信息

在开发环境中启用调试模式可以看到更详细的日志：

```yaml
pay_by_payment:
    debug: true
```

## 安全注意事项

1. **私钥安全**：确保私钥存储在安全的地方，不要提交到版本控制
2. **签名验证**：所有通知都必须验证签名
3. **HTTPS**：所有 API 调用必须使用 HTTPS
4. **金额验证**：在处理退款等操作时验证金额限制
5. **重复通知**：处理重复的通知，避免重复操作

## 支持的功能

✅ 订单管理
- 创建订单
- 查询订单
- 取消订单
- 支付结果通知

✅ 退款管理
- 创建退款
- 查询退款
- 退款结果通知

✅ 转账服务
- 内部转账
- 银行转账
- 转账结果通知

✅ 安全特性
- RSA 签名验证
- HTTPS 通信
- 错误处理
- 日志记录

✅ 数据管理
- 完整的实体映射
- 仓库模式
- 统计查询
- 数据验证