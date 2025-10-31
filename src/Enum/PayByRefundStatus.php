<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PayByRefundStatus: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case PENDING = 'PENDING';
    case PROCESSING = 'PROCESSING';
    case SUCCESS = 'SUCCESS';
    case FAILED = 'FAILED';
    case CANCELLED = 'CANCELLED';
    case UNKNOWN = 'UNKNOWN';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::PROCESSING => '处理中',
            self::SUCCESS => '退款成功',
            self::FAILED => '退款失败',
            self::CANCELLED => '已取消',
            self::UNKNOWN => '未知状态',
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
            self::UNKNOWN => 'dark',
        };
    }

    public function isFinal(): bool
    {
        return in_array($this, [self::SUCCESS, self::FAILED, self::CANCELLED, self::UNKNOWN], true);
    }
}
