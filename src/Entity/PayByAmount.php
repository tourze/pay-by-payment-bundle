<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Embeddable]
class PayByAmount
{
    /**
     * @var numeric-string
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 18, scale: 2, options: ['comment' => '金额'])]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private string $amount;

    #[ORM\Column(type: Types::STRING, length: 3, options: ['comment' => '货币代码'])]
    #[Assert\NotBlank]
    #[Assert\Currency]
    private string $currency;

    /**
     * @param numeric-string $amount
     */
    public function __construct(string $amount, string $currency = 'AED')
    {
        $this->amount = $amount;
        $this->currency = $currency;
    }

    /**
     * @return numeric-string
     */
    public function getAmount(): string
    {
        return $this->amount;
    }

    /**
     * @param numeric-string $amount
     */
    public function setAmount(string $amount): void
    {
        $this->amount = $amount;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    }

    public function getFormattedAmount(): string
    {
        return number_format((float) $this->amount, 2) . ' ' . $this->currency;
    }

    /**
     * @return array<string, string>
     */
    public function toArray(): array
    {
        return [
            'currency' => $this->currency,
            'amount' => $this->amount,
        ];
    }

    /**
     * @param array<string, string> $data
     */
    public static function fromArray(array $data): self
    {
        $amount = $data['amount'] ?? '0.00';
        assert(is_numeric($amount));

        return new self(
            $amount,
            $data['currency'] ?? 'AED'
        );
    }

    public function equals(self $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
