<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Request;

use Tourze\PayByPaymentBundle\Entity\PayByAmount;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;

class CreateOrderRequest
{
    private string $merchantOrderNo;

    private string $subject;

    private PayByAmount $totalAmount;

    private PayByPaySceneCode $paySceneCode;

    private ?string $notifyUrl = null;

    private ?string $returnUrl = null;

    /**
     * @var array<string, mixed>|null
     */
    private ?array $accessoryContent = null;

    public function __construct(
        string $merchantOrderNo,
        string $subject,
        PayByAmount $totalAmount,
        PayByPaySceneCode $paySceneCode,
    ) {
        $this->merchantOrderNo = $merchantOrderNo;
        $this->subject = $subject;
        $this->totalAmount = $totalAmount;
        $this->paySceneCode = $paySceneCode;
    }

    public function getMerchantOrderNo(): string
    {
        return $this->merchantOrderNo;
    }

    public function setMerchantOrderNo(string $merchantOrderNo): void
    {
        $this->merchantOrderNo = $merchantOrderNo;
    }

    public function getSubject(): string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): void
    {
        $this->subject = $subject;
    }

    public function getTotalAmount(): PayByAmount
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(PayByAmount $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    public function getPaySceneCode(): PayByPaySceneCode
    {
        return $this->paySceneCode;
    }

    public function setPaySceneCode(PayByPaySceneCode $paySceneCode): void
    {
        $this->paySceneCode = $paySceneCode;
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(?string $notifyUrl): void
    {
        $this->notifyUrl = $notifyUrl;
    }

    public function getReturnUrl(): ?string
    {
        return $this->returnUrl;
    }

    public function setReturnUrl(?string $returnUrl): void
    {
        $this->returnUrl = $returnUrl;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAccessoryContent(): ?array
    {
        return $this->accessoryContent;
    }

    /**
     * @param array<string, mixed>|null $accessoryContent
     */
    public function setAccessoryContent(?array $accessoryContent): void
    {
        $this->accessoryContent = $accessoryContent;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'merchantOrderNo' => $this->merchantOrderNo,
            'subject' => $this->subject,
            'totalAmount' => $this->totalAmount->toArray(),
            'paySceneCode' => $this->paySceneCode->value,
        ];

        if (null !== $this->notifyUrl) {
            $data['notifyUrl'] = $this->notifyUrl;
        }

        if (null !== $this->returnUrl) {
            $data['returnUrl'] = $this->returnUrl;
        }

        if (null !== $this->accessoryContent) {
            $data['accessoryContent'] = $this->accessoryContent;
        }

        return $data;
    }
}
