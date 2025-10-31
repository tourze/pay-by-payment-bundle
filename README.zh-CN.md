# pay-by-payment-bundle

[English](README.md) | [中文](README.zh-CN.md)

PayBy支付平台集成Bundle，支持订单管理、支付处理、退款和转账功能

## 功能特性

- PayBy支付平台集成
- 订单管理和支付处理
- 退款和转账功能
- 配置管理
- 数据统计和分析

## 安装

```bash
composer require tourze/pay-by-payment-bundle
```

## 控制台命令

### 配置管理

#### 创建PayBy配置
```bash
php bin/console payby:config:create
```
创建新的PayBy支付配置。

#### 列出PayBy配置
```bash
php bin/console payby:config:list
```
列出所有PayBy支付配置。

## 快速开始

```php
<?php

// 示例代码
```

## 贡献

详情请参阅 [CONTRIBUTING.md](CONTRIBUTING.md)。

## 许可

MIT 许可证 (MIT)。详情请参阅 [许可文件](LICENSE)。
