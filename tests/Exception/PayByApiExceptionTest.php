<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Exception\PayByApiException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(PayByApiException::class)]
class PayByApiExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionWithAllParameters(): void
    {
        $message = 'API request failed';
        $errorCode = 'INVALID_PAYMENT';
        $responseData = ['status' => 'error', 'details' => 'Payment method not supported'];

        $exception = new PayByApiException($message, $errorCode, $responseData);

        $this->assertInstanceOf(PayByApiException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
        $this->assertEquals($responseData, $exception->getResponseData());
    }

    public function testExceptionWithMinimalParameters(): void
    {
        $message = 'API error occurred';
        $exception = new PayByApiException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals('', $exception->getErrorCode());
        $this->assertEquals([], $exception->getResponseData());
    }

    public function testExceptionWithErrorCodeOnly(): void
    {
        $message = 'Network timeout';
        $errorCode = 'TIMEOUT_ERROR';
        $exception = new PayByApiException($message, $errorCode);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($errorCode, $exception->getErrorCode());
        $this->assertEquals([], $exception->getResponseData());
    }

    public function testExceptionInheritance(): void
    {
        $exception = new PayByApiException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
    }
}
