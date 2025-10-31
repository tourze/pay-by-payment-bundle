<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Exception\PayByException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(PayByException::class)]
class PayByExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInstantiation(): void
    {
        $message = 'Test exception message';
        $exception = new PayByException($message);

        $this->assertInstanceOf(PayByException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testExceptionWithCode(): void
    {
        $message = 'Test exception message';
        $code = 100;
        $exception = new PayByException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testExceptionWithPrevious(): void
    {
        $previousException = new \InvalidArgumentException('Previous exception');
        $exception = new PayByException('Test message', 0, $previousException);

        $this->assertSame($previousException, $exception->getPrevious());
    }
}
