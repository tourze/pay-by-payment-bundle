<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PayByTransferType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case INTERNAL = 'INTERNAL';
    case BANK_TRANSFER = 'BANK_TRANSFER';
    case TRANSFER_TO_BANK = 'TRANSFER_TO_BANK';
    case TRANSFER_TO_BALANCE = 'TRANSFER_TO_BALANCE';
    case TRANSFER_TO_THIRD_PARTY = 'TRANSFER_TO_THIRD_PARTY';

    public function getLabel(): string
    {
        return match ($this) {
            self::INTERNAL => '内部转账',
            self::BANK_TRANSFER => '银行转账',
            self::TRANSFER_TO_BANK => '转账到银行卡',
            self::TRANSFER_TO_BALANCE => '转账到余额',
            self::TRANSFER_TO_THIRD_PARTY => '转账到第三方',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::INTERNAL => 'PayBy系统内部账户间转账',
            self::BANK_TRANSFER => '转账到外部银行账户',
            self::TRANSFER_TO_BANK => '转账到外部银行卡账户',
            self::TRANSFER_TO_BALANCE => '转账到系统余额账户',
            self::TRANSFER_TO_THIRD_PARTY => '转账到第三方支付平台',
        };
    }
}
