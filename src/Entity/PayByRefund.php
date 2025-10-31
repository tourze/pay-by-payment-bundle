<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PayByPaymentBundle\Repository\PayByRefundRepository;

#[ORM\Entity(repositoryClass: PayByRefundRepository::class)]
#[ORM\Table(name: 'pay_by_refunds', options: ['comment' => 'PayBy退款记录'])]
class PayByRefund
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, options: ['comment' => 'PayBy退款ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $refundId;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '商户退款号'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $merchantRefundNo;

    #[ORM\ManyToOne(targetEntity: PayByOrder::class, inversedBy: 'refunds')]
    #[ORM\JoinColumn(nullable: false)]
    private PayByOrder $order;

    #[ORM\Embedded(class: PayByAmount::class)]
    #[Assert\Valid]
    private PayByAmount $refundAmount;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayByRefundStatus::class, options: ['comment' => '退款状态'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PayByRefundStatus::class, 'cases'])]
    private PayByRefundStatus $status;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '退款原因'])]
    #[Assert\Length(max: 255)]
    private ?string $refundReason = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '通知地址'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $notifyUrl = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展内容'])]
    #[Assert\Type(type: 'array')]
    private ?array $accessoryContent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'refund_time', nullable: true, options: ['comment' => '退款时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $refundTime = null;

    public function __construct()
    {
        $this->status = PayByRefundStatus::PENDING;
    }

    public function getRefundId(): string
    {
        return $this->refundId;
    }

    public function setRefundId(string $refundId): void
    {
        $this->refundId = $refundId;
    }

    public function getMerchantRefundNo(): string
    {
        return $this->merchantRefundNo;
    }

    public function setMerchantRefundNo(string $merchantRefundNo): void
    {
        $this->merchantRefundNo = $merchantRefundNo;
    }

    public function getOrder(): PayByOrder
    {
        return $this->order;
    }

    public function setOrder(PayByOrder $order): void
    {
        $this->order = $order;
    }

    public function getRefundAmount(): PayByAmount
    {
        return $this->refundAmount;
    }

    public function setRefundAmount(PayByAmount $refundAmount): void
    {
        $this->refundAmount = $refundAmount;
    }

    public function getStatus(): PayByRefundStatus
    {
        return $this->status;
    }

    public function setStatus(PayByRefundStatus $status): void
    {
        $this->status = $status;
    }

    public function getRefundReason(): ?string
    {
        return $this->refundReason;
    }

    public function setRefundReason(?string $refundReason): void
    {
        $this->refundReason = $refundReason;
    }

    public function setReason(?string $reason): void
    {
        $this->setRefundReason($reason);
    }

    public function getReason(): ?string
    {
        return $this->getRefundReason();
    }

    public function getNotifyUrl(): ?string
    {
        return $this->notifyUrl;
    }

    public function setNotifyUrl(?string $notifyUrl): void
    {
        $this->notifyUrl = $notifyUrl;
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

    public function getRefundTime(): ?\DateTimeImmutable
    {
        return $this->refundTime;
    }

    public function setRefundTime(?\DateTimeImmutable $refundTime): void
    {
        $this->refundTime = $refundTime;
    }

    public function isRefunded(): bool
    {
        return PayByRefundStatus::SUCCESS === $this->status;
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function getAmountFormatted(): string
    {
        return $this->refundAmount->getFormattedAmount();
    }

    /**
     * @deprecated Use getCreateTime() instead
     */
    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->getCreateTime();
    }

    /**
     * @deprecated Use setCreateTime() instead
     */
    public function setCreatedAt(?\DateTimeImmutable $createdAt): void
    {
        $this->setCreateTime($createdAt);
    }

    /**
     * @deprecated Use getUpdateTime() instead
     */
    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->getUpdateTime();
    }

    /**
     * @deprecated Use setUpdateTime() instead
     */
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->setUpdateTime($updatedAt);
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->refundId, $this->merchantRefundNo);
    }
}
