<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class PayByPaymentExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
