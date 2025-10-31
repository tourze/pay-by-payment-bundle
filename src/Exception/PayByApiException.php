<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Exception;

class PayByApiException extends \RuntimeException
{
    private string $errorCode;

    /**
     * @var array<string, mixed>
     */
    private array $responseData;

    /**
     * @param array<string, mixed> $responseData
     */
    public function __construct(string $message, string $errorCode = '', array $responseData = [])
    {
        parent::__construct($message);
        $this->errorCode = $errorCode;
        $this->responseData = $responseData;
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return array<string, mixed>
     */
    public function getResponseData(): array
    {
        return $this->responseData;
    }
}
