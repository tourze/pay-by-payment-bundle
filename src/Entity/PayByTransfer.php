<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\PayByPaymentBundle\Enum\PayByTransferStatus;
use Tourze\PayByPaymentBundle\Enum\PayByTransferType;
use Tourze\PayByPaymentBundle\Repository\PayByTransferRepository;

#[ORM\Entity(repositoryClass: PayByTransferRepository::class)]
#[ORM\Table(name: 'pay_by_transfers', options: ['comment' => 'PayBy转账记录'])]
class PayByTransfer
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\Column(type: Types::STRING, length: 100, unique: true, options: ['comment' => 'PayBy转账ID'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $transferId;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '商户转账号'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $merchantTransferNo;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayByTransferType::class, options: ['comment' => '转账类型'])]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PayByTransferType::class, 'cases'])]
    private PayByTransferType $transferType;

    #[ORM\Column(type: Types::STRING, length: 100, options: ['comment' => '转出账户'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $fromAccount;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '转入账户'])]
    #[IndexColumn]
    #[Assert\Length(max: 100)]
    private ?string $toAccount = null;

    #[ORM\Embedded(class: PayByAmount::class)]
    #[Assert\Valid]
    private PayByAmount $transferAmount;

    #[ORM\Column(type: Types::STRING, length: 20, enumType: PayByTransferStatus::class, options: ['comment' => '转账状态'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Choice(callback: [PayByTransferStatus::class, 'cases'])]
    private PayByTransferStatus $status;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '转账原因'])]
    #[Assert\Length(max: 255)]
    private ?string $transferReason = null;

    #[ORM\Column(type: Types::STRING, length: 512, nullable: true, options: ['comment' => '通知地址'])]
    #[Assert\Url]
    #[Assert\Length(max: 512)]
    private ?string $notifyUrl = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '银行转账信息'])]
    #[Assert\Type(type: 'array')]
    private ?array $bankTransferInfo = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '扩展内容'])]
    #[Assert\Type(type: 'array')]
    private ?array $accessoryContent = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, name: 'transfer_time', nullable: true, options: ['comment' => '转账时间'])]
    #[Assert\Type(type: \DateTimeImmutable::class)]
    private ?\DateTimeImmutable $transferTime = null;

    // 重新定义createTime字段以支持IndexColumn注解，参见 https://github.com/tourze/php-monorepo/issues/1451
    #[CreateTimeColumn]
    #[IndexColumn]
    #[ORM\Column(name: 'create_time', type: Types::DATETIME_IMMUTABLE, nullable: true, options: ['comment' => '创建时间'])]
    private ?\DateTimeImmutable $createTime = null; // @phpstan-ignore-line

    public function __construct()
    {
        $this->status = PayByTransferStatus::PENDING;
        $this->transferAmount = new PayByAmount('0.00', 'AED');
    }

    public function getTransferId(): string
    {
        return $this->transferId;
    }

    public function setTransferId(string $transferId): void
    {
        $this->transferId = $transferId;
    }

    public function getMerchantTransferNo(): string
    {
        return $this->merchantTransferNo;
    }

    public function setMerchantTransferNo(string $merchantTransferNo): void
    {
        $this->merchantTransferNo = $merchantTransferNo;
    }

    public function getTransferType(): PayByTransferType
    {
        return $this->transferType;
    }

    public function setTransferType(PayByTransferType $transferType): void
    {
        $this->transferType = $transferType;
    }

    public function getFromAccount(): string
    {
        return $this->fromAccount;
    }

    public function setFromAccount(string $fromAccount): void
    {
        $this->fromAccount = $fromAccount;
    }

    public function getToAccount(): ?string
    {
        return $this->toAccount;
    }

    public function setToAccount(?string $toAccount): void
    {
        $this->toAccount = $toAccount;
    }

    public function getTransferAmount(): PayByAmount
    {
        return $this->transferAmount;
    }

    public function setTransferAmount(PayByAmount $transferAmount): void
    {
        $this->transferAmount = $transferAmount;
    }

    /**
     * @return numeric-string
     */
    public function getAmount(): string
    {
        return $this->transferAmount->getAmount();
    }

    /**
     * @param numeric-string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->transferAmount->setAmount($amount);
    }

    public function getCurrency(): string
    {
        return $this->transferAmount->getCurrency();
    }

    public function setCurrency(string $currency): void
    {
        $this->transferAmount->setCurrency($currency);
    }

    public function getStatus(): PayByTransferStatus
    {
        return $this->status;
    }

    public function setStatus(PayByTransferStatus $status): void
    {
        $this->status = $status;
    }

    public function getTransferReason(): ?string
    {
        return $this->transferReason;
    }

    public function setTransferReason(?string $transferReason): void
    {
        $this->transferReason = $transferReason;
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
    /**
     * @return array<string, mixed>|null
     */
    public function getBankTransferInfo(): ?array
    {
        return $this->bankTransferInfo;
    }

    /**
     * @param array<string, mixed>|null $bankTransferInfo
     */
    /**
     * @param array<string, mixed>|null $bankTransferInfo
     */
    public function setBankTransferInfo(?array $bankTransferInfo): void
    {
        $this->bankTransferInfo = $bankTransferInfo;
    }

    /**
     * @return array<string, mixed>|null
     */
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
    /**
     * @param array<string, mixed>|null $accessoryContent
     */
    public function setAccessoryContent(?array $accessoryContent): void
    {
        $this->accessoryContent = $accessoryContent;
    }

    public function getTransferTime(): ?\DateTimeImmutable
    {
        return $this->transferTime;
    }

    public function setTransferTime(?\DateTimeImmutable $transferTime): void
    {
        $this->transferTime = $transferTime;
    }

    public function isTransferred(): bool
    {
        return PayByTransferStatus::SUCCESS === $this->status;
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
    public function setCreatedAt(\DateTimeImmutable $createdAt): void
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
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): void
    {
        $this->setUpdateTime($updatedAt);
    }

    public function isFinal(): bool
    {
        return $this->status->isFinal();
    }

    public function isBankTransfer(): bool
    {
        return PayByTransferType::BANK_TRANSFER === $this->transferType;
    }

    public function isInternalTransfer(): bool
    {
        return PayByTransferType::INTERNAL === $this->transferType;
    }

    public function getAmountFormatted(): string
    {
        return $this->transferAmount->getFormattedAmount();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->transferId, $this->merchantTransferNo);
    }
}
