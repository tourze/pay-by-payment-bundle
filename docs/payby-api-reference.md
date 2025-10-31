# PayBy API接口文档

## 概述

本文档详细介绍了PayBy支付平台的所有API接口，包括接口地址、请求参数、响应格式和错误码等信息。

**API基础URL**: `https://api.payby.com`  
**API版本**: v1  
**数据格式**: JSON  
**字符编码**: UTF-8

## 认证方式

### 私钥认证
所有API请求都需要使用私钥进行签名认证。

**签名算法**: RSA SHA256  
**私钥格式**: PEM格式的RSA私钥

### 请求头
```
Content-Type: application/json
X-PayBy-Signature: {签名}
X-PayBy-Timestamp: {时间戳}
```

## 通用响应格式

### 成功响应
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

### 错误响应
```json
{
    "code": "ERROR_CODE",
    "message": "错误描述",
    "data": null,
    "timestamp": "2024-01-01T12:00:00Z"
}
```

## 订单相关接口

### 1. 创建订单

**接口地址**: `POST /api/v1/orders`

**请求参数**:
```json
{
    "merchantOrderNo": "ORDER_123456",
    "subject": "商品名称",
    "totalAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "paySceneCode": "DYNQR",
    "notifyUrl": "https://yoursite.com/api/notification",
    "returnUrl": "https://yoursite.com/payment/result",
    "accessoryContent": {
        "amountDetail": {
            "vatAmount": {
                "currency": "AED",
                "amount": "5.00"
            }
        },
        "goodsDetail": {
            "body": "商品描述",
            "goodsName": "商品名称",
            "goodsId": "GOODS_001"
        },
        "terminalDetail": {
            "merchantName": "商户名称"
        }
    }
}
```

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "订单创建成功",
    "data": {
        "orderId": "PAYBY_ORDER_123456",
        "merchantOrderNo": "ORDER_123456",
        "qrCodeData": "二维码数据",
        "qrCodeUrl": "https://api.payby.com/qr/123456",
        "paymentUrl": "https://payby.com/pay/123456",
        "status": "PENDING",
        "createTime": "2024-01-01T12:00:00Z"
    }
}
```

### 2. 查询订单

**接口地址**: `GET /api/v1/orders/{orderId}`

**请求参数**: 无

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "查询成功",
    "data": {
        "orderId": "PAYBY_ORDER_123456",
        "merchantOrderNo": "ORDER_123456",
        "subject": "商品名称",
        "totalAmount": {
            "currency": "AED",
            "amount": "100.00"
        },
        "status": "SUCCESS",
        "paySceneCode": "DYNQR",
        "paymentMethod": "CREDIT_CARD",
        "createTime": "2024-01-01T12:00:00Z",
        "payTime": "2024-01-01T12:05:00Z",
        "notifyUrl": "https://yoursite.com/api/notification",
        "returnUrl": "https://yoursite.com/payment/result"
    }
}
```

### 3. 取消订单

**接口地址**: `POST /api/v1/orders/{orderId}/cancel`

**请求参数**:
```json
{
    "cancelReason": "客户取消"
}
```

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "订单取消成功",
    "data": {
        "orderId": "PAYBY_ORDER_123456",
        "status": "CANCELLED",
        "cancelTime": "2024-01-01T12:10:00Z"
    }
}
```

## 退款相关接口

### 1. 创建退款

**接口地址**: `POST /api/v1/refunds`

**请求参数**:
```json
{
    "merchantRefundNo": "REFUND_123456",
    "merchantOrderNo": "ORDER_123456",
    "refundAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "refundReason": "客户取消订单",
    "notifyUrl": "https://yoursite.com/api/refund-notification",
    "accessoryContent": {
        "refundDetail": {
            "refundType": "FULL",
            "originalAmount": {
                "currency": "AED",
                "amount": "100.00"
            }
        }
    }
}
```

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "退款申请成功",
    "data": {
        "refundId": "PAYBY_REFUND_123456",
        "merchantRefundNo": "REFUND_123456",
        "merchantOrderNo": "ORDER_123456",
        "refundAmount": {
            "currency": "AED",
            "amount": "100.00"
        },
        "status": "PENDING",
        "createTime": "2024-01-01T12:00:00Z"
    }
}
```

### 2. 查询退款

**接口地址**: `GET /api/v1/refunds/{refundId}`

**请求参数**: 无

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "查询成功",
    "data": {
        "refundId": "PAYBY_REFUND_123456",
        "merchantRefundNo": "REFUND_123456",
        "merchantOrderNo": "ORDER_123456",
        "refundAmount": {
            "currency": "AED",
            "amount": "100.00"
        },
        "status": "SUCCESS",
        "refundReason": "客户取消订单",
        "createTime": "2024-01-01T12:00:00Z",
        "refundTime": "2024-01-01T12:05:00Z"
    }
}
```

## 转账相关接口

### 1. 创建内部转账

**接口地址**: `POST /api/v1/transfers`

**请求参数**:
```json
{
    "merchantTransferNo": "TRANSFER_123456",
    "fromAccount": "SENDER_ACCOUNT_ID",
    "toAccount": "RECEIVER_ACCOUNT_ID",
    "transferAmount": {
        "currency": "AED",
        "amount": "1000.00"
    },
    "transferReason": "业务往来",
    "notifyUrl": "https://yoursite.com/api/transfer-notification",
    "accessoryContent": {
        "transferDetail": {
            "transferType": "INTERNAL",
            "priority": "NORMAL",
            "memo": "转账备注"
        },
        "senderDetail": {
            "senderName": "张三",
            "senderPhone": "+971501234567"
        },
        "receiverDetail": {
            "receiverName": "李四",
            "receiverPhone": "+971509876543"
        }
    }
}
```

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "转账申请成功",
    "data": {
        "transferId": "PAYBY_TRANSFER_123456",
        "merchantTransferNo": "TRANSFER_123456",
        "fromAccount": "SENDER_ACCOUNT_ID",
        "toAccount": "RECEIVER_ACCOUNT_ID",
        "transferAmount": {
            "currency": "AED",
            "amount": "1000.00"
        },
        "status": "PENDING",
        "createTime": "2024-01-01T12:00:00Z"
    }
}
```

### 2. 创建银行转账

**接口地址**: `POST /api/v1/transfers/bank`

**请求参数**:
```json
{
    "merchantTransferNo": "BANK_TRANSFER_123456",
    "fromAccount": "SENDER_ACCOUNT_ID",
    "bankTransferInfo": {
        "bankCode": "ENBD",
        "accountNumber": "1234567890",
        "accountName": "李四",
        "swiftCode": "EBILAEAD",
        "iban": "AE123456789012345678901"
    },
    "transferAmount": {
        "currency": "AED",
        "amount": "5000.00"
    },
    "transferReason": "投资理财",
    "notifyUrl": "https://yoursite.com/api/bank-transfer-notification",
    "accessoryContent": {
        "bankTransferDetail": {
            "transferType": "BANK_TRANSFER",
            "priority": "NORMAL",
            "expectedArrivalTime": "2-3 business days"
        },
        "senderDetail": {
            "senderName": "张三",
            "senderPhone": "+971501234567",
            "senderAddress": {
                "country": "AE",
                "state": "Dubai",
                "city": "Dubai",
                "address": "123 Main Street"
            }
        }
    }
}
```

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "银行转账申请成功",
    "data": {
        "transferId": "PAYBY_BANK_TRANSFER_123456",
        "merchantTransferNo": "BANK_TRANSFER_123456",
        "fromAccount": "SENDER_ACCOUNT_ID",
        "bankTransferInfo": {
            "bankCode": "ENBD",
            "accountNumber": "1234567890",
            "accountName": "李四"
        },
        "transferAmount": {
            "currency": "AED",
            "amount": "5000.00"
        },
        "status": "PENDING",
        "createTime": "2024-01-01T12:00:00Z"
    }
}
```

### 3. 查询转账

**接口地址**: `GET /api/v1/transfers/{transferId}`

**请求参数**: 无

**响应参数**:
```json
{
    "code": "SUCCESS",
    "message": "查询成功",
    "data": {
        "transferId": "PAYBY_TRANSFER_123456",
        "merchantTransferNo": "TRANSFER_123456",
        "fromAccount": "SENDER_ACCOUNT_ID",
        "toAccount": "RECEIVER_ACCOUNT_ID",
        "transferAmount": {
            "currency": "AED",
            "amount": "1000.00"
        },
        "status": "SUCCESS",
        "transferReason": "业务往来",
        "createTime": "2024-01-01T12:00:00Z",
        "transferTime": "2024-01-01T12:05:00Z"
    }
}
```

## 异步通知接口

### 1. 支付结果通知

**通知地址**: 商户配置的notifyUrl

**通知参数**:
```json
{
    "orderId": "PAYBY_ORDER_123456",
    "merchantOrderNo": "ORDER_123456",
    "status": "SUCCESS",
    "totalAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "paymentMethod": "CREDIT_CARD",
    "payTime": "2024-01-01T12:05:00Z",
    "signature": "签名"
}
```

### 2. 退款结果通知

**通知地址**: 商户配置的notifyUrl

**通知参数**:
```json
{
    "refundId": "PAYBY_REFUND_123456",
    "merchantRefundNo": "REFUND_123456",
    "merchantOrderNo": "ORDER_123456",
    "status": "SUCCESS",
    "refundAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "refundTime": "2024-01-01T12:05:00Z",
    "signature": "签名"
}
```

### 3. 转账结果通知

**通知地址**: 商户配置的notifyUrl

**通知参数**:
```json
{
    "transferId": "PAYBY_TRANSFER_123456",
    "merchantTransferNo": "TRANSFER_123456",
    "status": "SUCCESS",
    "transferAmount": {
        "currency": "AED",
        "amount": "1000.00"
    },
    "transferTime": "2024-01-01T12:05:00Z",
    "signature": "签名"
}
```

## 错误码说明

### 通用错误码

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| SUCCESS | 操作成功 | - |
| INVALID_PARAMETER | 参数错误 | 检查请求参数格式和必填项 |
| INVALID_SIGNATURE | 签名错误 | 检查签名算法和私钥配置 |
| AUTHENTICATION_FAILED | 认证失败 | 检查私钥是否正确 |
| INSUFFICIENT_BALANCE | 余额不足 | 检查账户余额 |
| ORDER_NOT_FOUND | 订单不存在 | 检查订单ID是否正确 |
| ORDER_ALREADY_PAID | 订单已支付 | 检查订单状态 |
| TRANSFER_LIMIT_EXCEEDED | 转账限额超限 | 检查转账限额设置 |
| SYSTEM_ERROR | 系统错误 | 联系技术支持 |

### 业务错误码

| 错误码 | 说明 | 解决方案 |
|--------|------|----------|
| PAYMENT_DECLINED | 支付被拒绝 | 检查支付方式和账户状态 |
| REFUND_AMOUNT_EXCEEDED | 退款金额超限 | 检查退款金额是否超过订单金额 |
| BANK_ACCOUNT_INVALID | 银行账户无效 | 检查银行账户信息 |
| TRANSFER_TO_SAME_ACCOUNT | 转账到同一账户 | 检查发送方和接收方账户 |
| CURRENCY_NOT_SUPPORTED | 货币不支持 | 检查货币代码 |

## 签名算法

### 签名生成步骤

1. **准备参数**: 将所有请求参数按字母顺序排序
2. **构建签名字符串**: 将参数按key=value格式拼接，用&连接
3. **添加时间戳**: 在签名字符串末尾添加时间戳
4. **计算签名**: 使用私钥对签名字符串进行RSA签名

### 签名示例

```php
function generateSignature($params, $privateKey) {
    // 1. 按字母顺序排序参数
    ksort($params);
    
    // 2. 构建签名字符串
    $signString = '';
    foreach ($params as $key => $value) {
        if ($key !== 'signature' && !empty($value)) {
            $signString .= $key . '=' . $value . '&';
        }
    }
    
    // 3. 添加时间戳
    $timestamp = time();
    $signString .= 'timestamp=' . $timestamp;
    
    // 4. 计算签名
    $signature = '';
    openssl_sign($signString, $signature, $privateKey, OPENSSL_ALGO_SHA256);
    
    return [
        'signature' => base64_encode($signature),
        'timestamp' => $timestamp
    ];
}
```

### 签名验证

```php
function verifySignature($params, $signature, $publicKey) {
    // 1. 提取签名字符串
    $signString = '';
    foreach ($params as $key => $value) {
        if ($key !== 'signature' && !empty($value)) {
            $signString .= $key . '=' . $value . '&';
        }
    }
    $signString = rtrim($signString, '&');
    
    // 2. 验证签名
    $signatureData = base64_decode($signature);
    return openssl_verify($signString, $signatureData, $publicKey, OPENSSL_ALGO_SHA256) === 1;
}
```

## 限流规则

### API调用频率限制

| 接口类型 | 频率限制 | 说明 |
|----------|----------|------|
| 订单查询 | 100次/分钟 | 每个商户 |
| 订单创建 | 50次/分钟 | 每个商户 |
| 退款申请 | 20次/分钟 | 每个商户 |
| 转账申请 | 30次/分钟 | 每个商户 |
| 状态查询 | 200次/分钟 | 每个商户 |

### 超限处理

当API调用频率超过限制时，系统会返回以下错误：

```json
{
    "code": "RATE_LIMIT_EXCEEDED",
    "message": "请求频率超限，请稍后重试",
    "data": null,
    "timestamp": "2024-01-01T12:00:00Z"
}
```

## 测试环境

### 测试配置

```bash
# 测试环境API地址
API_BASE_URL=https://test-api.payby.com
TEST_PRIVATE_KEY=your_test_private_key
```

### 测试数据

| 字段 | 测试值 | 说明 |
|------|--------|------|
| 金额 | 0.01-1000.00 | 测试金额范围 |
| 货币 | AED | 测试货币 |
| 订单号 | TEST_ORDER_* | 测试订单号前缀 |
| 银行卡 | 4111111111111111 | 测试卡号 |

## API使用示例

### 创建订单

```bash
curl -X POST https://api.payby.com/v1/orders \
  -H "Content-Type: application/json" \
  -H "X-PayBy-Signature: {签名}" \
  -H "X-PayBy-Timestamp: {时间戳}" \
  -d '{
    "merchantOrderNo": "ORDER_123456",
    "subject": "测试商品",
    "totalAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "paySceneCode": "DYNQR",
    "notifyUrl": "https://yoursite.com/api/notification"
  }'
```

### 其他语言示例

- **Java**: 使用HttpClient或OkHttp
- **Python**: 使用requests库
- **Node.js**: 使用axios或fetch
- **Go**: 使用net/http包

## 技术支持

### 联系方式

- **技术支持邮箱**: support@payby.com
- **开发者文档**: https://developers.payby.com/docs
- **API文档**: https://developers.payby.com/docs/api
- **API状态监控**: https://status.payby.com

### 常见问题

1. **如何获取API密钥？**
   - 登录PayBy商户后台
   - 进入API管理页面
   - 生成新的API密钥

2. **如何处理异步通知？**
   - 确保notifyUrl可访问
   - 验证签名确保数据安全
   - 返回成功响应避免重复通知

3. **如何测试API接口？**
   - 使用测试环境配置
   - 使用测试数据
   - 查看测试日志

4. **如何处理API错误？**
   - 查看错误码说明
   - 检查请求参数
   - 联系技术支持

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本发布
- 支持订单、退款、转账功能
- 提供完整的API文档

### v1.1.0 (2024-02-01)
- 新增批量转账功能
- 优化错误处理机制
- 增加更多支付方式支持

### v1.2.0 (2024-03-01)
- 新增跨境转账功能
- 优化签名算法
- 增加限流保护机制 