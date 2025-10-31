<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Attribute\WithMonologChannel;
use Tourze\PayByPaymentBundle\DependencyInjection\PayByPaymentExtension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(PayByPaymentExtension::class)]
class PayByPaymentExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    protected function getExtensionClass(): string
    {
        return PayByPaymentExtension::class;
    }
}
