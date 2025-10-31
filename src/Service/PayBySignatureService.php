<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Exception\PayByException;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'pay_by_payment')]
class PayBySignatureService
{
    public function __construct(
        ?string $privateKey = null,
        ?string $publicKey = null,
        ?LoggerInterface $logger = null,
    ) {
        $envPrivateKey = $_ENV['PAY_BY_PRIVATE_KEY'] ?? '';
        $this->privateKey = $privateKey ?? (is_string($envPrivateKey) ? $envPrivateKey : '');

        $envPublicKey = $_ENV['PAY_BY_PUBLIC_KEY'] ?? '';
        $this->publicKey = $publicKey ?? (is_string($envPublicKey) ? $envPublicKey : '');

        $this->logger = $logger ?? new NullLogger();
    }

    private string $privateKey;

    private string $publicKey;

    private LoggerInterface $logger;

    /**
     * @param array<string, mixed> $params
     * @return array<string, string|int>
     */
    public function generateSignature(array $params): array
    {
        $timestamp = time();
        $signString = $this->buildSignString($params, $timestamp);

        $this->logger->debug('Generating signature', [
            'sign_string' => $signString,
            'timestamp' => $timestamp,
        ]);

        $signatureBytes = '';
        if (!openssl_sign($signString, $signatureBytes, $this->privateKey, OPENSSL_ALGO_SHA256)) {
            $this->logger->error('Failed to generate signature', [
                'error' => openssl_error_string(),
            ]);
            throw new PayByException('Failed to generate signature');
        }

        if (!is_string($signatureBytes)) {
            throw new PayByException('Invalid signature bytes');
        }

        return [
            'signature' => base64_encode($signatureBytes),
            'timestamp' => $timestamp,
        ];
    }

    /**
     * @param array<string, mixed> $params
     */
    public function verifySignature(array $params, string $signature): bool
    {
        $signString = $this->buildSignString($params);

        $this->logger->debug('Verifying signature', [
            'sign_string' => $signString,
            'signature' => $signature,
        ]);

        $signatureData = base64_decode($signature, true);
        if (false === $signatureData) {
            $this->logger->error('Failed to decode signature');

            return false;
        }

        $result = openssl_verify($signString, $signatureData, $this->publicKey, OPENSSL_ALGO_SHA256);

        if (-1 === $result) {
            $this->logger->error('Signature verification error', [
                'error' => openssl_error_string(),
            ]);

            return false;
        }

        return 1 === $result;
    }

    /**
     * @param array<string, mixed> $params
     */
    private function buildSignString(array $params, ?int $timestamp = null): string
    {
        ksort($params);

        $signString = '';
        foreach ($params as $key => $value) {
            if ('signature' !== $key && '' !== $value && null !== $value && false !== $value) {
                $valueStr = is_scalar($value) ? (string) $value : '';
                $signString .= $key . '=' . $valueStr . '&';
            }
        }
        $signString = rtrim($signString, '&');

        if (null !== $timestamp) {
            $signString .= '&timestamp=' . $timestamp;
        }

        return $signString;
    }
}
