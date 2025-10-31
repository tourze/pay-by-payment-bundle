<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\PayByPaymentBundle\Repository\PayByConfigRepository;

#[ORM\Entity(repositoryClass: PayByConfigRepository::class)]
#[ORM\Table(name: 'pay_by_config', options: ['comment' => 'PayBy支付配置'])]
class PayByConfig implements \Stringable
{
    use SnowflakeKeyAware;
    use TimestampableAware;

    #[ORM\Column(length: 255, unique: true, options: ['comment' => '配置名称'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '配置描述'])]
    #[Assert\Length(max: 1000)]
    private string $description;

    #[ORM\Column(length: 255, options: ['comment' => 'API基础URL'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    #[Assert\Url]
    private string $apiBaseUrl;

    #[ORM\Column(length: 255, options: ['comment' => 'API密钥'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $apiKey;

    #[ORM\Column(length: 255, options: ['comment' => 'API密钥'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $apiSecret;

    #[ORM\Column(length: 255, options: ['comment' => '商户ID'])]
    #[Assert\Length(max: 255)]
    #[Assert\NotBlank]
    private string $merchantId;

    #[ORM\Column(length: 512, nullable: true, options: ['comment' => '回调URL'])]
    #[Assert\Length(max: 512)]
    #[Assert\Url]
    private ?string $callbackUrl = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true, 'comment' => '是否启用'])]
    #[Assert\Type(type: 'bool')]
    private bool $enabled = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 30, 'comment' => '超时时间(秒)'])]
    #[Assert\Type(type: 'integer')]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(value: 300)]
    private int $timeout = 30;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 3, 'comment' => '重试次数'])]
    #[Assert\Type(type: 'integer')]
    #[Assert\Positive]
    #[Assert\LessThanOrEqual(value: 10)]
    private int $retryAttempts = 3;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true, options: ['comment' => '额外配置'])]
    #[Assert\Type(type: 'array')]
    private ?array $extraConfig = [];

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false, 'comment' => '是否默认配置'])]
    #[Assert\Type(type: 'bool')]
    private bool $isDefault = false;

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
        $this->updateTime = new \DateTimeImmutable();
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    public function getApiBaseUrl(): string
    {
        return $this->apiBaseUrl;
    }

    public function setApiBaseUrl(string $apiBaseUrl): void
    {
        $this->apiBaseUrl = $apiBaseUrl;
    }

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function setApiKey(string $apiKey): void
    {
        $this->apiKey = $apiKey;
    }

    public function getApiSecret(): string
    {
        return $this->apiSecret;
    }

    public function setApiSecret(string $apiSecret): void
    {
        $this->apiSecret = $apiSecret;
    }

    public function getCallbackUrl(): ?string
    {
        return $this->callbackUrl;
    }

    public function setCallbackUrl(?string $callbackUrl): void
    {
        $this->callbackUrl = $callbackUrl;
    }

    public function getMerchantId(): string
    {
        return $this->merchantId;
    }

    public function setMerchantId(string $merchantId): void
    {
        $this->merchantId = $merchantId;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function setEnabled(bool $enabled): void
    {
        $this->enabled = $enabled;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function setRetryAttempts(int $retryAttempts): void
    {
        $this->retryAttempts = $retryAttempts;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getExtraConfig(): ?array
    {
        return $this->extraConfig;
    }

    /**
     * @param array<string, mixed>|null $extraConfig
     */
    public function setExtraConfig(?array $extraConfig): void
    {
        $this->extraConfig = $extraConfig;
    }

    public function isDefault(): bool
    {
        return $this->isDefault;
    }

    public function setDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function __toString(): string
    {
        return $this->name;
    }

    public function setCode(string $code): void
    {
        $this->name = $code;
    }

    public function setPartnerName(string $partnerName): void
    {
        $this->description = $partnerName;
    }

    public function setSigningAlgorithm(string $signingAlgorithm): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['signing_algorithm'] = $signingAlgorithm;
    }

    public function setAppId(string $appId): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['app_id'] = $appId;
    }

    public function setPrivateKey(string $privateKey): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['private_key'] = $privateKey;
    }

    public function setPublicKey(string $publicKey): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['public_key'] = $publicKey;
    }

    public function setCertificatePath(string $certificatePath): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['certificate_path'] = $certificatePath;
    }

    public function setVersion(string $version): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['version'] = $version;
    }

    public function setEnableNotify(bool $enableNotify): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['enable_notify'] = $enableNotify;
    }

    public function setNotifyUrl(string $notifyUrl): void
    {
        $this->callbackUrl = $notifyUrl;
    }

    public function setSandbox(bool $sandbox): void
    {
        if (null === $this->extraConfig) {
            $this->extraConfig = [];
        }
        $this->extraConfig['sandbox'] = $sandbox;
    }

    public function setGatewayUrl(string $gatewayUrl): void
    {
        $this->apiBaseUrl = $gatewayUrl;
    }

    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    public function getCode(): string
    {
        return $this->name;
    }

    public function getPartnerName(): string
    {
        return $this->description;
    }

    public function getSigningAlgorithm(): ?string
    {
        $value = $this->extraConfig['signing_algorithm'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function getAppId(): ?string
    {
        $value = $this->extraConfig['app_id'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function getPrivateKey(): ?string
    {
        $value = $this->extraConfig['private_key'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function getPublicKey(): ?string
    {
        $value = $this->extraConfig['public_key'] ?? null;

        return is_string($value) ? $value : null;
    }

    public function getGatewayUrl(): string
    {
        return $this->apiBaseUrl;
    }

    public function isSandbox(): bool
    {
        $value = $this->extraConfig['sandbox'] ?? false;

        return is_bool($value) ? $value : false;
    }
}
