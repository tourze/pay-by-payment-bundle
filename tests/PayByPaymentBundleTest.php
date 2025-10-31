<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\PayByPaymentBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(PayByPaymentBundle::class)]
#[RunTestsInSeparateProcesses]
class PayByPaymentBundleTest extends AbstractBundleTestCase
{
    public function testBundleInstantiation(): void
    {
        $bundleClass = static::getBundleClass();
        $bundle = new $bundleClass();
        $this->assertInstanceOf(PayByPaymentBundle::class, $bundle);
    }
}
