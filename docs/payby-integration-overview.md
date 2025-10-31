# PayBy支付平台接入概述

## 平台简介

PayBy是一家专注于阿联酋市场的数字支付解决方案提供商，为商家提供安全、便捷的无现金支付服务。PayBy致力于通过创新的全渠道支付解决方案加速业务增长。

**官方网站**: https://payby.com  
**开发者文档**: https://developers.payby.com/docs/integration-guide  
**API文档**: https://developers.payby.com/docs/api

## 支持的支付场景

PayBy支付平台支持以下主要支付场景：

### 1. 动态二维码支付 (DYNQR)
- **场景代码**: `DYNQR`
- **适用场景**: 线下实体店、移动支付
- **特点**: 生成动态二维码，用户扫码支付

### 2. 在线支付
- **适用场景**: 电商网站、移动应用
- **特点**: 集成支付网关，支持多种支付方式

### 3. 退款处理
- **功能**: 支持订单退款
- **特点**: 完整的退款流程管理

### 4. 转账服务
- **功能**: 账户间转账、银行转账
- **特点**: 支持内部转账和外部银行转账

## 技术架构

### API接口规范
- **API基础URL**: `https://api.payby.com`
- **API版本**: v1
- **数据格式**: JSON
- **字符编码**: UTF-8

### 核心功能
1. **订单管理**: 创建、查询、取消订单
2. **支付处理**: 多种支付场景支持
3. **退款管理**: 退款申请和查询
4. **转账服务**: 内部转账和银行转账
5. **通知回调**: 异步支付结果通知

## 安全特性

- **私钥认证**: 使用私钥进行API调用认证
- **签名验证**: 支持回调通知签名验证
- **HTTPS通信**: 所有API调用使用HTTPS加密传输
- **数据加密**: 敏感数据传输加密

## 货币支持

- **主要货币**: AED (阿联酋迪拉姆)
- **其他货币**: 根据业务需求支持多种货币

## 集成优势

1. **简单易用**: 提供完整的PHP SDK
2. **文档完善**: 详细的API文档和示例代码
3. **技术支持**: 专业的开发者支持团队
4. **合规性**: 符合阿联酋支付行业标准
5. **可扩展性**: 支持多种业务场景扩展

## 快速开始

### API认证
所有API请求都需要使用私钥进行签名认证。

### 创建订单示例
```bash
curl -X POST https://api.payby.com/v1/orders \
  -H "Content-Type: application/json" \
  -H "X-PayBy-Signature: {签名}" \
  -H "X-PayBy-Timestamp: {时间戳}" \
  -d '{
    "merchantOrderNo": "ORDER_123456",
    "subject": "商品名称",
    "totalAmount": {
        "currency": "AED",
        "amount": "100.00"
    },
    "paySceneCode": "DYNQR",
    "notifyUrl": "https://yoursite.com/api/notification"
  }'
```

## 相关文档

- [动态二维码支付接入指南](./payby-dynqr-payment.md)
- [在线支付接入指南](./payby-online-payment.md)
- [退款处理指南](./payby-refund-process.md)
- [转账服务指南](./payby-transfer-service.md)
- [API接口文档](./payby-api-reference.md) 