<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Repository\PayByConfigRepository;

#[WithMonologChannel(channel: 'pay_by_payment')]
class PayByConfigManager
{
    public function __construct(
        private readonly PayByConfigRepository $configRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getConfig(string $name): ?PayByConfig
    {
        return $this->configRepository->findByName($name);
    }

    public function getDefaultConfig(): ?PayByConfig
    {
        return $this->configRepository->findDefaultConfig();
    }

    /**
     * @return array<PayByConfig>
     */
    public function getAllEnabledConfigs(): array
    {
        return $this->configRepository->findEnabledConfigs();
    }

    /**
     * @param array<string, mixed> $data
     */
    public function createConfig(array $data): PayByConfig
    {
        $config = new PayByConfig();
        $this->setCreateConfigFields($config, $data);

        if ($config->isDefault()) {
            $this->clearDefaultFlags();
        }

        $this->entityManager->persist($config);
        $this->entityManager->flush();

        $this->logger->info('PayBy 配置已创建', [
            'config_id' => $config->getId(),
            'config_name' => $config->getName(),
        ]);

        return $config;
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateConfig(string $name, array $data): ?PayByConfig
    {
        $config = $this->getConfig($name);
        if (null === $config) {
            return null;
        }

        $this->updateConfigFields($config, $data);
        $this->handleDefaultFlag($config, $data);

        $this->entityManager->flush();

        $this->logger->info('PayBy 配置已更新', [
            'config_id' => $config->getId(),
            'config_name' => $config->getName(),
        ]);

        return $config;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateConfigFields(PayByConfig $config, array $data): void
    {
        $this->updateStringFields($config, $data);
        $this->updateBoolFields($config, $data);
        $this->updateIntFields($config, $data);
        $this->updateArrayFields($config, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateStringFields(PayByConfig $config, array $data): void
    {
        if (isset($data['description']) && is_string($data['description'])) {
            $config->setDescription($data['description']);
        }
        if (isset($data['apiBaseUrl']) && is_string($data['apiBaseUrl'])) {
            $config->setApiBaseUrl($data['apiBaseUrl']);
        }
        if (isset($data['apiKey']) && is_string($data['apiKey'])) {
            $config->setApiKey($data['apiKey']);
        }
        if (isset($data['apiSecret']) && is_string($data['apiSecret'])) {
            $config->setApiSecret($data['apiSecret']);
        }
        if (isset($data['merchantId']) && is_string($data['merchantId'])) {
            $config->setMerchantId($data['merchantId']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateBoolFields(PayByConfig $config, array $data): void
    {
        if (isset($data['enabled']) && is_bool($data['enabled'])) {
            $config->setEnabled($data['enabled']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateIntFields(PayByConfig $config, array $data): void
    {
        if (isset($data['timeout']) && is_int($data['timeout'])) {
            $config->setTimeout($data['timeout']);
        }
        if (isset($data['retryAttempts']) && is_int($data['retryAttempts'])) {
            $config->setRetryAttempts($data['retryAttempts']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateArrayFields(PayByConfig $config, array $data): void
    {
        if (isset($data['extraConfig']) && is_array($data['extraConfig'])) {
            /** @var array<string, mixed> $safeExtraConfig */
            $safeExtraConfig = $data['extraConfig'];
            $config->setExtraConfig($safeExtraConfig);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function handleDefaultFlag(PayByConfig $config, array $data): void
    {
        if (isset($data['isDefault'])) {
            $isDefault = (bool) $data['isDefault'];
            if ($isDefault) {
                $this->clearDefaultFlags();
            }
            $config->setDefault($isDefault);
        }
    }

    public function deleteConfig(string $name): bool
    {
        $config = $this->getConfig($name);
        if (null === $config) {
            return false;
        }

        $this->entityManager->remove($config);
        $this->entityManager->flush();

        $this->logger->info('PayBy 配置已删除', [
            'config_id' => $config->getId(),
            'config_name' => $config->getName(),
        ]);

        return true;
    }

    private function clearDefaultFlags(): void
    {
        $configs = $this->configRepository->findBy(['isDefault' => true]);
        foreach ($configs as $config) {
            $config->setDefault(false);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setCreateConfigFields(PayByConfig $config, array $data): void
    {
        $this->setRequiredFields($config, $data);
        $this->setOptionalFields($config, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setRequiredFields(PayByConfig $config, array $data): void
    {
        $name = $data['name'] ?? '';
        if (!is_string($name)) {
            throw new \InvalidArgumentException('name must be string');
        }
        $config->setName($name);

        $apiBaseUrl = $data['apiBaseUrl'] ?? '';
        if (!is_string($apiBaseUrl)) {
            throw new \InvalidArgumentException('apiBaseUrl must be string');
        }
        $config->setApiBaseUrl($apiBaseUrl);

        $apiKey = $data['apiKey'] ?? '';
        if (!is_string($apiKey)) {
            throw new \InvalidArgumentException('apiKey must be string');
        }
        $config->setApiKey($apiKey);

        $merchantId = $data['merchantId'] ?? '';
        if (!is_string($merchantId)) {
            throw new \InvalidArgumentException('merchantId must be string');
        }
        $config->setMerchantId($merchantId);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function setOptionalFields(PayByConfig $config, array $data): void
    {
        $description = $data['description'] ?? '';
        $config->setDescription(is_string($description) ? $description : '');

        $apiSecret = $data['apiSecret'] ?? '';
        $config->setApiSecret(is_string($apiSecret) ? $apiSecret : '');

        $enabled = $data['enabled'] ?? true;
        $config->setEnabled(is_bool($enabled) ? $enabled : true);

        $isDefault = $data['isDefault'] ?? false;
        $config->setDefault(is_bool($isDefault) ? $isDefault : false);

        $timeout = $data['timeout'] ?? 30;
        $config->setTimeout(is_int($timeout) ? $timeout : 30);

        $retryAttempts = $data['retryAttempts'] ?? 3;
        $config->setRetryAttempts(is_int($retryAttempts) ? $retryAttempts : 3);

        $extraConfig = $data['extraConfig'] ?? [];
        if (is_array($extraConfig)) {
            /** @var array<string, mixed> $safeExtraConfig */
            $safeExtraConfig = $extraConfig;
            $config->setExtraConfig($safeExtraConfig);
        } else {
            $config->setExtraConfig([]);
        }
    }
}
