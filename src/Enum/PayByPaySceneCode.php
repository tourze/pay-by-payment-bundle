<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum PayByPaySceneCode: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case DYNQR = 'DYNQR';
    case ONLINE = 'ONLINE';
    case IN_STORE = 'IN_STORE';
    case MOBILE = 'MOBILE';
    case WEB = 'WEB';
    case MINI_PROGRAM = 'MINI_PROGRAM';
    case APP = 'APP';
    case H5 = 'H5';
    case PAYPAGE = 'PAYPAGE';

    public function getLabel(): string
    {
        return match ($this) {
            self::DYNQR => '动态二维码支付',
            self::ONLINE => '在线支付',
            self::IN_STORE => '店内支付',
            self::MOBILE => '移动支付',
            self::WEB => '网页支付',
            self::MINI_PROGRAM => '小程序支付',
            self::APP => 'APP支付',
            self::H5 => 'H5支付',
            self::PAYPAGE => '支付页面',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::DYNQR => '生成动态二维码，用户扫码支付',
            self::ONLINE => '集成支付网关，支持多种支付方式',
            self::IN_STORE => '线下实体店支付',
            self::MOBILE => '移动应用内支付',
            self::WEB => '浏览器内网页支付',
            self::MINI_PROGRAM => '微信小程序内支付',
            self::APP => '手机应用内支付',
            self::H5 => '手机H5页面支付',
            self::PAYPAGE => '独立支付页面',
        };
    }
}
