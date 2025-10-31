# PayBy Payment Bundle 设计规范

## 架构概述

### 设计原则
- **简洁性**：API 设计简洁易用
- **可扩展性**：支持功能扩展和定制
- **可靠性**：确保支付过程的可靠性
- **安全性**：内置安全验证和保护机制

### 系统架构
```
┌─────────────────────────────────────────────────────────┐
│                    PayBy Payment Bundle                    │
├─────────────────────────────────────────────────────────┤
│  Controller Layer  │  Service Layer  │  Repository Layer   │
├─────────────────────────────────────────────────────────┤
│  PayByNotification  │  PayByOrderService  │  PayByOrderRepository   │
│  Controller         │  PayByRefundService  │  PayByRefundRepository   │
│                    │  PayByTransferService│  PayByTransferRepository │
├─────────────────────────────────────────────────────────┤
│                    PayByApiClient                         │
├─────────────────────────────────────────────────────────┤
│                    PayBy API                              │
└─────────────────────────────────────────────────────────┘
```

## 核心组件设计

### 1. 实体层 (Entity Layer)

#### PayByOrder
```php
class PayByOrder
{
    // 核心属性
    - orderId: string          // PayBy 订单ID
    - merchantOrderNo: string  // 商户订单号
    - subject: string           // 订单标题
    - totalAmount: PayByAmount // 订单金额
    - status: PayByOrderStatus // 订单状态
    - paySceneCode: PayByPaySceneCode // 支付场景
    
    // 支付信息
    - paymentMethod: string   // 支付方式
    - qrCodeData: string      // 二维码数据
    - paymentUrl: string      // 支付链接
    
    // 通知信息
    - notifyUrl: string       // 通知地址
    - returnUrl: string        // 返回地址
    
    // 时间信息
    - createTime: DateTimeImmutable
    - updateTime: DateTimeImmutable
    - payTime: DateTimeImmutable
}
```

#### PayByAmount (值对象)
```php
class PayByAmount
{
    - amount: string    // 金额（字符串格式）
    - currency: string // 货币代码
    
    // 方法
    + getFormattedAmount(): string
    + toArray(): array
    + fromArray(array $data): self
    + equals(self $other): bool
}
```

### 2. 仓库层 (Repository Layer)

#### 基础仓库接口
```php
interface PayByRepositoryInterface
{
    + save(Entity $entity, bool $flush = false): void
    + remove(Entity $entity, bool $flush = false): void
    + findById(string $id): ?Entity
    + findByMerchantNo(string $merchantNo): ?Entity
}
```

#### 具体仓库实现
```php
class PayByOrderRepository extends ServiceEntityRepository 
    implements PayByRepositoryInterface
{
    + findPendingOrders(): array
    + findPaidOrders(): array
    + findOrdersByDateRange(DateTime $start, DateTime $end): array
    + getOrderStatistics(?DateTime $start, ?DateTime $end): array
}
```

### 3. 服务层 (Service Layer)

#### PayByOrderService
```php
class PayByOrderService
{
    // 核心方法
    + createOrder(string $merchantOrderNo, string $subject, 
                 PayByAmount $totalAmount, PayByPaySceneCode $paySceneCode,
                 ?string $notifyUrl = null, ?string $returnUrl = null): PayByOrder
    
    + queryOrder(string $orderId): ?PayByOrder
    + cancelOrder(string $orderId, string $cancelReason = ''): bool
    + handlePaymentNotification(array $notificationData): bool
    
    // 业务方法
    + isOrderPaid(string $orderId): bool
    + canCancelOrder(string $orderId): bool
    + getRefundableAmount(string $orderId): string
}
```

#### PayByApiClient (HTTP 客户端)
```php
class PayByApiClient
{
    // API 方法
    + createOrder(CreateOrderRequest $request): array
    + queryOrder(string $orderId): array
    + cancelOrder(string $orderId, string $cancelReason = ''): array
    + createRefund(array $data): array
    + queryRefund(string $refundId): array
    + createTransfer(array $data): array
    + queryTransfer(string $transferId): array
    
    // 通用方法
    + request(string $method, string $path, array $data = []): array
    + generateSignature(array $params): array
    + verifySignature(array $params, string $signature): bool
}
```

### 4. 控制器层 (Controller Layer)

#### PayByNotificationController
```php
class PayByNotificationController extends AbstractController
{
    #[Route('/api/pay-by/notification', methods: ['POST'])]
    public function handleNotification(Request $request): JsonResponse
    {
        // 1. 验证签名
        // 2. 解析数据
        // 3. 处理通知
        // 4. 返回响应
    }
}
```

## 数据库设计

### 核心表结构

#### pay_by_orders (订单表)
```sql
CREATE TABLE pay_by_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'PayBy订单ID',
    merchant_order_no VARCHAR(100) NOT NULL COMMENT '商户订单号',
    subject VARCHAR(255) NOT NULL COMMENT '订单标题',
    total_amount_currency VARCHAR(3) NOT NULL COMMENT '货币代码',
    total_amount_amount DECIMAL(18,2) NOT NULL COMMENT '订单金额',
    pay_scene_code VARCHAR(20) NOT NULL COMMENT '支付场景代码',
    status VARCHAR(20) NOT NULL COMMENT '订单状态',
    payment_method VARCHAR(50) NULL COMMENT '支付方式',
    qr_code_data TEXT NULL COMMENT '二维码数据',
    qr_code_url VARCHAR(512) NULL COMMENT '二维码URL',
    payment_url VARCHAR(512) NULL COMMENT '支付链接',
    notify_url VARCHAR(512) NULL COMMENT '通知地址',
    return_url VARCHAR(512) NULL COMMENT '返回地址',
    accessory_content JSON NULL COMMENT '扩展内容',
    pay_time DATETIME NULL COMMENT '支付时间',
    create_time DATETIME NOT NULL COMMENT '创建时间',
    update_time DATETIME NULL COMMENT '更新时间',
    
    INDEX idx_merchant_order_no (merchant_order_no),
    INDEX idx_status (status),
    INDEX idx_create_time (create_time)
);
```

#### pay_by_refunds (退款表)
```sql
CREATE TABLE pay_by_refunds (
    id INT AUTO_INCREMENT PRIMARY KEY,
    refund_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'PayBy退款ID',
    merchant_refund_no VARCHAR(100) NOT NULL COMMENT '商户退款号',
    order_id INT NOT NULL COMMENT '订单ID',
    refund_amount_currency VARCHAR(3) NOT NULL COMMENT '货币代码',
    refund_amount_amount DECIMAL(18,2) NOT NULL COMMENT '退款金额',
    status VARCHAR(20) NOT NULL COMMENT '退款状态',
    refund_reason VARCHAR(255) NULL COMMENT '退款原因',
    notify_url VARCHAR(512) NULL COMMENT '通知地址',
    accessory_content JSON NULL COMMENT '扩展内容',
    refund_time DATETIME NULL COMMENT '退款时间',
    create_time DATETIME NOT NULL COMMENT '创建时间',
    update_time DATETIME NULL COMMENT '更新时间',
    
    FOREIGN KEY (order_id) REFERENCES pay_by_orders(id),
    INDEX idx_merchant_refund_no (merchant_refund_no),
    INDEX idx_order_id (order_id),
    INDEX idx_status (status),
    INDEX idx_create_time (create_time)
);
```

#### pay_by_transfers (转账表)
```sql
CREATE TABLE pay_by_transfers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transfer_id VARCHAR(100) UNIQUE NOT NULL COMMENT 'PayBy转账ID',
    merchant_transfer_no VARCHAR(100) NOT NULL COMMENT '商户转账号',
    transfer_type VARCHAR(20) NOT NULL COMMENT '转账类型',
    from_account VARCHAR(100) NOT NULL COMMENT '转出账户',
    to_account VARCHAR(100) NULL COMMENT '转入账户',
    transfer_amount_currency VARCHAR(3) NOT NULL COMMENT '货币代码',
    transfer_amount_amount DECIMAL(18,2) NOT NULL COMMENT '转账金额',
    status VARCHAR(20) NOT NULL COMMENT '转账状态',
    transfer_reason VARCHAR(255) NULL COMMENT '转账原因',
    notify_url VARCHAR(512) NULL COMMENT '通知地址',
    bank_transfer_info JSON NULL COMMENT '银行转账信息',
    accessory_content JSON NULL COMMENT '扩展内容',
    transfer_time DATETIME NULL COMMENT '转账时间',
    create_time DATETIME NOT NULL COMMENT '创建时间',
    update_time DATETIME NULL COMMENT '更新时间',
    
    INDEX idx_merchant_transfer_no (merchant_transfer_no),
    INDEX idx_from_account (from_account),
    INDEX idx_to_account (to_account),
    INDEX idx_status (status),
    INDEX idx_create_time (create_time)
);
```

## API 设计

### 1. 统一响应格式
```json
{
    "code": "SUCCESS",
    "message": "操作成功",
    "data": {
        // 具体数据
    },
    "timestamp": "2024-01-01T12:00:00Z"
}
```

### 2. 错误响应格式
```json
{
    "code": "ERROR_CODE",
    "message": "错误描述",
    "data": null,
    "timestamp": "2024-01-01T12:00:00Z"
}
```

### 3. 核心接口

#### 订单接口
```php
// 创建订单
POST /api/v1/orders
{
    "merchantOrderNo": "ORDER_123456",
    "subject": "测试商品",
    "totalAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "paySceneCode": "DYNQR",
    "notifyUrl": "https://example.com/notify"
}

// 查询订单
GET /api/v1/orders/{orderId}

// 取消订单
POST /api/v1/orders/{orderId}/cancel
{
    "cancelReason": "客户取消"
}
```

#### 退款接口
```php
// 创建退款
POST /api/v1/refunds
{
    "merchantRefundNo": "REFUND_123456",
    "merchantOrderNo": "ORDER_123456",
    "refundAmount": {
        "currency": "AED",
        "amount": "50.00"
    },
    "refundReason": "客户取消"
}

// 查询退款
GET /api/v1/refunds/{refundId}
```

#### 转账接口
```php
// 创建转账
POST /api/v1/transfers
{
    "merchantTransferNo": "TRANSFER_123456",
    "fromAccount": "ACCOUNT_001",
    "toAccount": "ACCOUNT_002",
    "transferAmount": {
        "currency": "AED",
        "amount": "1000.00"
    },
    "transferReason": "业务往来"
}

// 查询转账
GET /api/v1/transfers/{transferId}
```

## 安全设计

### 1. 签名验证
```php
class PayBySignatureService
{
    + generateSignature(array $params): array
    + verifySignature(array $params, string $signature): bool
}
```

### 2. 请求拦截
```php
class PayByRequestInterceptor
{
    + interceptRequest(Request $request): bool
    + validateSignature(Request $request): bool
    + validateTimestamp(Request $request): bool
}
```

### 3. 异常处理
```php
class PayByApiException extends RuntimeException
{
    private string $errorCode;
    private array $responseData;
    
    + getErrorCode(): string
    + getResponseData(): array
}
```

## 事件系统

### 1. 事件定义
```php
class PayByOrderEvent
{
    const CREATED = 'payby.order.created';
    const PAID = 'payby.order.paid';
    const CANCELLED = 'payby.order.cancelled';
    const FAILED = 'payby.order.failed';
}

class PayByRefundEvent
{
    const CREATED = 'payby.refund.created';
    const COMPLETED = 'payby.refund.completed';
    const FAILED = 'payby.refund.failed';
}

class PayByTransferEvent
{
    const CREATED = 'payby.transfer.created';
    const COMPLETED = 'payby.transfer.completed';
    const FAILED = 'payby.transfer.failed';
}
```

### 2. 事件监听器
```php
class PayByOrderEventListener
{
    + onOrderCreated(PayByOrderEvent $event): void
    + onOrderPaid(PayByOrderEvent $event): void
    + onOrderCancelled(PayByOrderEvent $event): void
    + onOrderFailed(PayByOrderEvent $event): void
}
```

## 配置设计

### 1. 配置结构
```yaml
# config/packages/pay_by_payment.yaml
pay_by_payment:
    api_base_url: 'https://api.payby.com'
    api_version: 'v1'
    private_key: '%env(PAYBY_PRIVATE_KEY)%'
    public_key: '%env(PAYBY_PUBLIC_KEY)%'
    timeout: 30
    debug: false
    
    # 支付场景配置
    pay_scenes:
        dynqr:
            enabled: true
            timeout: 300
        online:
            enabled: true
            timeout: 600
    
    # 监控配置
    monitoring:
        enabled: true
        log_level: 'info'
        metrics_enabled: true
```

### 2. 环境变量
```env
# PayBy 配置
PAYBY_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----..."
PAYBY_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----..."
PAYBY_API_BASE_URL="https://api.payby.com"
PAYBY_API_VERSION="v1"
PAYBY_TIMEOUT="30"
PAYBY_DEBUG="false"
```

## 测试设计

### 1. 单元测试
```php
class PayByOrderServiceTest extends TestCase
{
    + testCreateOrder(): void
    + testQueryOrder(): void
    + testCancelOrder(): void
    + testHandlePaymentNotification(): void
}
```

### 2. 集成测试
```php
class PayByApiClientIntegrationTest extends TestCase
{
    + testCreateOrderApi(): void
    + testQueryOrderApi(): void
    + testSignatureVerification(): void
}
```

### 3. 端到端测试
```php
class PayByPaymentE2ETest extends TestCase
{
    + testCompletePaymentFlow(): void
    + testRefundFlow(): void
    + testTransferFlow(): void
}
```

## 监控和日志

### 1. 日志设计
```php
class PayByLogger
{
    + logOrderCreated(PayByOrder $order): void
    + logPaymentReceived(array $notification): void
    + logApiCall(string $method, string $url, array $data): void
    + logError(Exception $exception): void
}
```

### 2. 监控指标
```php
class PayByMetrics
{
    + incrementOrderCount(): void
    + incrementPaymentAmount(float $amount): void
    + recordApiCallTime(string $method, float $time): void
    + incrementErrorCount(string $errorType): void
}
```

## 扩展点设计

### 1. 支付方式扩展
```php
interface PayByPaymentMethodInterface
{
    + getName(): string
    + processPayment(array $data): array
    + validatePayment(array $data): bool
}
```

### 2. 通知处理器扩展
```php
interface PayByNotificationHandlerInterface
{
    + handleNotification(array $data): bool
    + supports(string $notificationType): bool
}
```

### 3. 中间件扩展
```php
interface PayByMiddlewareInterface
{
    + process(Request $request, callable $next): Response
    + getPriority(): int
}
```

这个设计提供了完整的 PayBy Payment Bundle 架构，确保了代码的可维护性、可扩展性和安全性。