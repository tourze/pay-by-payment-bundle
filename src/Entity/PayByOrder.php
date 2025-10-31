<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\PayByPaymentBundle\Enum\PayByOrderStatus;
use Tourze\PayByPaymentBundle\Enum\PayByPaySceneCode;
use Tourze\PayByPaymentBundle\Enum\PayByRefundStatus;
use Tourze\PayByPaymentBundle\Repository\PayByOrderRepository;

#[ORM\Entity(repositoryClass: PayByOrderRepository::class)]
#[ORM\Table(name: 'pay_by_orders', options: ['comment' => 'PayBy支付订单'])]
class PayByOrder
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, options: ['comment' => 'PayBy订单ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $orderId;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '商户订单号'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $merchantOrderNo;

    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '订单标题'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $subject;

    #[ORM\Embedded(class: PayByAmount::class)]
    #[Assert\Valid]
    private ?PayByAmount $totalAmount = null;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayByPaySceneCode::class, options: ['comment' => '支付场景代码'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PayByPaySceneCode::class, 'cases'])]
    private PayByPaySceneCode $paySceneCode;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayByOrderStatus::class, options: ['comment' => '订单状态'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PayByOrderStatus::class, 'cases'])]
    private PayByOrderStatus $status;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '支付方式'])]
    #[Assert\Length(max: 255)]
    private ?string $paymentMethod = null;

    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '二维码数据'])]
    #[Assert\Length(max: 65535)]
    private ?string $qrCodeData = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '二维码URL'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $qrCodeUrl = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '支付链接'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $paymentUrl = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '通知地址'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $notifyUrl = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '返回地址'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $returnUrl = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展内容'])]
    #[Assert\Type(type: 'array')]
    private ?array $accessoryContent = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '订单描述内容'])]
    #[Assert\Length(max: 500)]
    private ?string $body = null;

    #[ORM\ManyToOne(targetEntity: PayByConfig::class)]
    #[ORM\JoinColumn(name: 'config_id', referencedColumnName: 'id')]
    private ?PayByConfig $config = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'pay_time', nullable: true, options: ['comment' => '支付时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $payTime = null;

    /**
     * @var Collection<int, PayByRefund>
     */
    #[ORM\OneToMany(targetEntity: PayByRefund::class, mappedBy: 'order')]
    private Collection $refunds;

    public function __construct()
    {
        $this->status = PayByOrderStatus::PENDING;
        $this->refunds = new ArrayCollection();
        $this->totalAmount = new PayByAmount('0.00', 'AED');
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }

    public function setOrderId(string $orderId): void
    {
        $this->orderId = $orderId;
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

    public function getTotalAmount(): ?PayByAmount
    {
        return $this->totalAmount;
    }

    public function setTotalAmount(?PayByAmount $totalAmount): void
    {
        $this->totalAmount = $totalAmount;
    }

    /**
     * @return numeric-string
     */
    public function getAmount(): string
    {
        return $this->totalAmount?->getAmount() ?? '0.00';
    }

    /**
     * @param numeric-string $amount
     */
    public function setAmount(string $amount): void
    {
        if (null === $this->totalAmount) {
            $this->totalAmount = new PayByAmount($amount);
        } else {
            $this->totalAmount->setAmount($amount);
        }
    }

    public function getCurrency(): string
    {
        return $this->totalAmount?->getCurrency() ?? 'AED';
    }

    public function setCurrency(string $currency): void
    {
        if (null === $this->totalAmount) {
            $this->totalAmount = new PayByAmount('0.00', $currency);
        } else {
            $this->totalAmount->setCurrency($currency);
        }
    }

    public function getPaySceneCode(): PayByPaySceneCode
    {
        return $this->paySceneCode;
    }

    public function setPaySceneCode(PayByPaySceneCode $paySceneCode): void
    {
        $this->paySceneCode = $paySceneCode;
    }

    public function getStatus(): PayByOrderStatus
    {
        return $this->status;
    }

    public function setStatus(PayByOrderStatus $status): void
    {
        $this->status = $status;
    }

    public function getPaymentMethod(): ?string
    {
        return $this->paymentMethod;
    }

    public function setPaymentMethod(?string $paymentMethod): void
    {
        $this->paymentMethod = $paymentMethod;
    }

    public function getQrCodeData(): ?string
    {
        return $this->qrCodeData;
    }

    public function setQrCodeData(?string $qrCodeData): void
    {
        $this->qrCodeData = $qrCodeData;
    }

    public function getQrCodeUrl(): ?string
    {
        return $this->qrCodeUrl;
    }

    public function setQrCodeUrl(?string $qrCodeUrl): void
    {
        $this->qrCodeUrl = $qrCodeUrl;
    }

    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    public function setPaymentUrl(?string $paymentUrl): void
    {
        $this->paymentUrl = $paymentUrl;
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

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(?string $body): void
    {
        $this->body = $body;
    }

    public function getConfig(): ?PayByConfig
    {
        return $this->config;
    }

    public function setConfig(?PayByConfig $config): void
    {
        $this->config = $config;
    }

    public function getPayTime(): ?\DateTimeImmutable
    {
        return $this->payTime;
    }

    public function setPayTime(?\DateTimeImmutable $payTime): void
    {
        $this->payTime = $payTime;
    }

    /**
     * @return Collection<int, PayByRefund>
     */
    public function getRefunds(): Collection
    {
        return $this->refunds;
    }

    public function addRefund(PayByRefund $refund): void
    {
        if (!$this->refunds->contains($refund)) {
            $this->refunds->add($refund);
            $refund->setOrder($this);
        }
    }

    public function removeRefund(PayByRefund $refund): void
    {
        if ($this->refunds->removeElement($refund)) {
            // 退款记录必须关联到订单，移除关系后退款记录应该被删除
            // 这里不设置 null，因为数据库约束不允许
        }
    }

    public function isPaid(): bool
    {
        return PayByOrderStatus::SUCCESS === $this->status;
    }

    public function canBeCancelled(): bool
    {
        return $this->status->canBeCancelled();
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function getRefundableAmount(): string
    {
        if (!$this->isPaid() || null === $this->totalAmount) {
            return '0.00';
        }

        $refundedAmount = '0.00';
        foreach ($this->refunds as $refund) {
            if (PayByRefundStatus::SUCCESS === $refund->getStatus()) {
                $amount = $refund->getRefundAmount()->getAmount();
                $refundedAmount = bcadd($refundedAmount, $amount, 2);
            }
        }

        $totalAmount = $this->totalAmount->getAmount();

        return bcsub($totalAmount, $refundedAmount, 2);
    }

    public function getAmountFormatted(): string
    {
        if (null === $this->totalAmount) {
            return '0.00 AED';
        }

        return $this->totalAmount->getFormattedAmount();
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
        return sprintf('%s (%s)', $this->orderId, $this->subject);
    }
}
