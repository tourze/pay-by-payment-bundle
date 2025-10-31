<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PayByOrderStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case TIMEOUT = 'TIMEOUT';
    case REFUNDED = 'REFUNDED';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待支付',
            self::PROCESSING => '处理中',
            self::SUCCESS => '支付成功',
            self::FAILED => '支付失败',
            self::CANCELLED => '已取消',
            self::TIMEOUT => '超时',
            self::REFUNDED => '已退款',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PROCESSING => 'info',
            self::SUCCESS => 'success',
            self::FAILED => 'danger',
            self::CANCELLED => 'secondary',
            self::TIMEOUT => 'dark',
            self::REFUNDED => 'secondary',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::SUCCESS, self::FAILED, self::CANCELLED, self::TIMEOUT, self::REFUNDED], true);
    }

    public function canBeCancelled(): bool
    {
        return in_array($this, [self::PENDING, self::PROCESSING], true);
    }
}
