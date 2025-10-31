<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Service\PayByConfigManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(PayByConfigManager::class)]
#[RunTestsInSeparateProcesses]
final class PayByConfigManagerTest extends AbstractIntegrationTestCase
{
    private PayByConfigManager $configManager;

    protected function onSetUp(): void
    {
        $this->configManager = self::getService(PayByConfigManager::class);
    }

    public function testGetConfigFound(): void
    {
        $configName = 'test-config-' . uniqid();

        // Create a test config first
        $config = new PayByConfig();
        $config->setName($configName);
        $config->setDescription('Test configuration');
        $config->setApiKey('test-key');
        $config->setApiSecret('test-secret');
        $config->setMerchantId('test-merchant');
        $config->setApiBaseUrl('https://api.test.com');

        $entityManager = self::getEntityManager();
        $entityManager->persist($config);
        $entityManager->flush();

        $result = $this->configManager->getConfig($configName);

        $this->assertNotNull($result);
        $this->assertEquals($configName, $result->getName());
    }

    public function testGetConfigNotFound(): void
    {
        $configName = 'nonexistent-config';

        $result = $this->configManager->getConfig($configName);

        $this->assertNull($result);
    }

    public function testCreateConfigSuccess(): void
    {
        $configData = [
            'name' => 'new-config-' . uniqid(),
            'description' => 'Test configuration',
            'apiBaseUrl' => 'https://api.test.payby.com',
            'apiKey' => 'test-api-key',
            'merchantId' => 'test-merchant',
            'enabled' => true,
            'timeout' => 60,
            'retryAttempts' => 5,
            'extraConfig' => ['debug' => true],
            'isDefault' => false,
        ];

        $result = $this->configManager->createConfig($configData);

        $this->assertInstanceOf(PayByConfig::class, $result);
        $this->assertEquals($configData['name'], $result->getName());
        $this->assertEquals($configData['description'], $result->getDescription());
        $this->assertEquals($configData['apiBaseUrl'], $result->getApiBaseUrl());
        $this->assertEquals($configData['apiKey'], $result->getApiKey());
        $this->assertEquals($configData['merchantId'], $result->getMerchantId());
        $this->assertEquals($configData['enabled'], $result->isEnabled());
        $this->assertEquals($configData['timeout'], $result->getTimeout());
        $this->assertEquals($configData['retryAttempts'], $result->getRetryAttempts());
        $this->assertEquals($configData['extraConfig'], $result->getExtraConfig());
        $this->assertEquals($configData['isDefault'], $result->isDefault());
    }

    public function testCreateConfigWithDefaults(): void
    {
        $configData = [
            'name' => 'minimal-config-' . uniqid(),
            'apiBaseUrl' => 'https://api.payby.com',
            'apiKey' => 'api-key',
            'merchantId' => 'merchant-123',
        ];

        $result = $this->configManager->createConfig($configData);

        $this->assertInstanceOf(PayByConfig::class, $result);
        $this->assertEquals($configData['name'], $result->getName());
        $this->assertEquals('', $result->getDescription());
        $this->assertTrue($result->isEnabled());
        $this->assertEquals(30, $result->getTimeout());
        $this->assertEquals(3, $result->getRetryAttempts());
        $this->assertEquals([], $result->getExtraConfig());
        $this->assertFalse($result->isDefault());
    }

    public function testUpdateConfigSuccess(): void
    {
        $configName = 'existing-config-' . uniqid();

        // Create config first
        $existingConfig = new PayByConfig();
        $existingConfig->setName($configName);
        $existingConfig->setDescription('Old description');
        $existingConfig->setTimeout(30);
        $existingConfig->setApiKey('test-key');
        $existingConfig->setApiSecret('test-secret');
        $existingConfig->setMerchantId('test-merchant');
        $existingConfig->setApiBaseUrl('https://api.test.com');

        $entityManager = self::getEntityManager();
        $entityManager->persist($existingConfig);
        $entityManager->flush();

        $updateData = [
            'description' => 'Updated description',
            'timeout' => 45,
            'enabled' => false,
        ];

        $result = $this->configManager->updateConfig($configName, $updateData);

        $this->assertNotNull($result);
        $this->assertEquals($updateData['description'], $result->getDescription());
        $this->assertEquals($updateData['timeout'], $result->getTimeout());
        $this->assertEquals($updateData['enabled'], $result->isEnabled());
    }

    public function testUpdateConfigNotFound(): void
    {
        $configName = 'nonexistent-config';
        $updateData = ['description' => 'Updated description'];

        $result = $this->configManager->updateConfig($configName, $updateData);

        $this->assertNull($result);
    }

    public function testDeleteConfigSuccess(): void
    {
        $configName = 'config-to-delete-' . uniqid();

        // Create config first
        $configToDelete = new PayByConfig();
        $configToDelete->setName($configName);
        $configToDelete->setDescription('Config to delete');
        $configToDelete->setApiKey('test-key');
        $configToDelete->setApiSecret('test-secret');
        $configToDelete->setMerchantId('test-merchant');
        $configToDelete->setApiBaseUrl('https://api.test.com');

        $entityManager = self::getEntityManager();
        $entityManager->persist($configToDelete);
        $entityManager->flush();

        $result = $this->configManager->deleteConfig($configName);

        $this->assertTrue($result);

        // Verify it's deleted
        $deleted = $this->configManager->getConfig($configName);
        $this->assertNull($deleted);
    }

    public function testDeleteConfigNotFound(): void
    {
        $configName = 'nonexistent-config';

        $result = $this->configManager->deleteConfig($configName);

        $this->assertFalse($result);
    }
}
