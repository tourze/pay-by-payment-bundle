<?php

declare(strict_types=1);

require_once __DIR__ . '/../src/Entity/PayByAmount.php';
require_once __DIR__ . '/../src/Enum/PayByOrderStatus.php';
require_once __DIR__ . '/../src/Enum/PayByPaySceneCode.php';

use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;

// 简单的功能测试
function testPayByAmount(): void
{
    echo "Testing PayByAmount...\n";

    $amount = new PayByAmount('100.50', 'AED');
    assert('100.50' === $amount->getAmount());
    assert('AED' === $amount->getCurrency());
    assert('100.50 AED' === $amount->getFormattedAmount());

    $amountFromArray = PayByAmount::fromArray(['amount' => '200.75', 'currency' => 'USD']);
    assert('200.75' === $amountFromArray->getAmount());
    assert('USD' === $amountFromArray->getCurrency());

    echo "✓ PayByAmount tests passed\n";
}

function testPayByOrderStatus(): void
{
    echo "Testing PayByOrderStatus...\n";

    assert('待支付' === PayByOrderStatus::PENDING->getLabel());
    assert('支付成功' === PayByOrderStatus::SUCCESS->getLabel());
    assert('已取消' === PayByOrderStatus::CANCELLED->getLabel());

    assert(!PayByOrderStatus::PENDING->isFinal());
    assert(PayByOrderStatus::SUCCESS->isFinal());
    assert(PayByOrderStatus::CANCELLED->isFinal());

    assert(PayByOrderStatus::PENDING->canBeCancelled());
    assert(!PayByOrderStatus::SUCCESS->canBeCancelled());
    assert(!PayByOrderStatus::CANCELLED->canBeCancelled());

    echo "✓ PayByOrderStatus tests passed\n";
}

function testPayByPaySceneCode(): void
{
    echo "Testing PayByPaySceneCode...\n";

    assert('动态二维码支付' === PayByPaySceneCode::DYNQR->getLabel());
    assert('在线支付' === PayByPaySceneCode::ONLINE->getLabel());

    assert('生成动态二维码，用户扫码支付' === PayByPaySceneCode::DYNQR->getDescription());
    assert('集成支付网关，支持多种支付方式' === PayByPaySceneCode::ONLINE->getDescription());

    echo "✓ PayByPaySceneCode tests passed\n";
}

// 运行测试
testPayByAmount();
testPayByOrderStatus();
testPayByPaySceneCode();

echo "\n🎉 All tests passed successfully!\n";
