<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Repository\PayByConfigRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(PayByConfigRepository::class)]
#[RunTestsInSeparateProcesses]
class PayByConfigRepositoryTest extends AbstractRepositoryTestCase
{
    private PayByConfigRepository $repository;

    protected function onSetUp(): void
    {
        $this->repository = self::getService(PayByConfigRepository::class);
    }

    protected function getRepository(): PayByConfigRepository
    {
        return $this->repository;
    }

    protected function createNewEntity(): PayByConfig
    {
        $config = new PayByConfig();
        $config->setName('test-config-' . uniqid());
        $config->setDescription('Test PayBy configuration');
        $config->setApiBaseUrl('https://api.payby.com');
        $config->setApiKey('test-api-key');
        $config->setApiSecret('test-api-secret');
        $config->setMerchantId('test-merchant-id');
        $config->setCallbackUrl('https://example.com/callback');
        $config->setEnabled(true);
        $config->setTimeout(30);
        $config->setRetryAttempts(3);
        $config->setExtraConfig(['env' => 'test']);
        $config->setDefault(false);

        return $config;
    }

    public function testSaveAndFind(): void
    {
        $config = $this->createNewEntity();
        $this->repository->save($config, true);

        $foundConfig = $this->repository->find($config->getId());

        $this->assertNotNull($foundConfig);
        $this->assertSame($config->getName(), $foundConfig->getName());
        $this->assertSame($config->getApiBaseUrl(), $foundConfig->getApiBaseUrl());
        $this->assertSame($config->getMerchantId(), $foundConfig->getMerchantId());
    }

    public function testRemove(): void
    {
        $config = $this->createNewEntity();
        $this->repository->save($config, true);
        $id = $config->getId();

        $this->repository->remove($config, true);

        $foundConfig = $this->repository->find($id);
        $this->assertNull($foundConfig);
    }

    public function testFindEnabledConfigs(): void
    {
        // 创建启用的配置
        $enabledConfig1 = $this->createNewEntity();
        $enabledConfig1->setName('enabled-config-1');
        $enabledConfig1->setEnabled(true);
        $this->repository->save($enabledConfig1, true);

        $enabledConfig2 = $this->createNewEntity();
        $enabledConfig2->setName('enabled-config-2');
        $enabledConfig2->setEnabled(true);
        $this->repository->save($enabledConfig2, true);

        // 创建禁用的配置
        $disabledConfig = $this->createNewEntity();
        $disabledConfig->setName('disabled-config');
        $disabledConfig->setEnabled(false);
        $this->repository->save($disabledConfig, true);

        $enabledConfigs = $this->repository->findEnabledConfigs();

        $this->assertCount(2, $enabledConfigs);
        $names = array_map(fn (PayByConfig $config) => $config->getName(), $enabledConfigs);
        $this->assertContains('enabled-config-1', $names);
        $this->assertContains('enabled-config-2', $names);
        $this->assertNotContains('disabled-config', $names);

        // 验证排序（按name ASC）
        $this->assertSame('enabled-config-1', $enabledConfigs[0]->getName());
        $this->assertSame('enabled-config-2', $enabledConfigs[1]->getName());
    }

    public function testFindEnabledConfigsWhenNoneEnabled(): void
    {
        $disabledConfig = $this->createNewEntity();
        $disabledConfig->setName('disabled-config');
        $disabledConfig->setEnabled(false);
        $this->repository->save($disabledConfig, true);

        $enabledConfigs = $this->repository->findEnabledConfigs();

        $this->assertEmpty($enabledConfigs);
    }

    public function testFindDefaultConfig(): void
    {
        // 创建非默认配置
        $nonDefaultConfig = $this->createNewEntity();
        $nonDefaultConfig->setName('non-default-config');
        $nonDefaultConfig->setEnabled(true);
        $nonDefaultConfig->setDefault(false);
        $this->repository->save($nonDefaultConfig, true);

        // 创建默认配置
        $defaultConfig = $this->createNewEntity();
        $defaultConfig->setName('default-config');
        $defaultConfig->setEnabled(true);
        $defaultConfig->setDefault(true);
        $this->repository->save($defaultConfig, true);

        $foundDefaultConfig = $this->repository->findDefaultConfig();

        $this->assertNotNull($foundDefaultConfig);
        $this->assertSame('default-config', $foundDefaultConfig->getName());
        $this->assertTrue($foundDefaultConfig->isDefault());
        $this->assertTrue($foundDefaultConfig->isEnabled());
    }

    public function testFindDefaultConfigWhenNoneExists(): void
    {
        $nonDefaultConfig = $this->createNewEntity();
        $nonDefaultConfig->setName('non-default-config');
        $nonDefaultConfig->setEnabled(true);
        $nonDefaultConfig->setDefault(false);
        $this->repository->save($nonDefaultConfig, true);

        $foundDefaultConfig = $this->repository->findDefaultConfig();

        $this->assertNull($foundDefaultConfig);
    }

    public function testFindDefaultConfigWhenDefaultIsDisabled(): void
    {
        $disabledDefaultConfig = $this->createNewEntity();
        $disabledDefaultConfig->setName('disabled-default-config');
        $disabledDefaultConfig->setEnabled(false);
        $disabledDefaultConfig->setDefault(true);
        $this->repository->save($disabledDefaultConfig, true);

        $foundDefaultConfig = $this->repository->findDefaultConfig();

        $this->assertNull($foundDefaultConfig);
    }

    public function testFindByName(): void
    {
        $config = $this->createNewEntity();
        $config->setName('unique-config-name');
        $this->repository->save($config, true);

        $foundConfig = $this->repository->findByName('unique-config-name');

        $this->assertNotNull($foundConfig);
        $this->assertSame('unique-config-name', $foundConfig->getName());
        $this->assertSame($config->getId(), $foundConfig->getId());
    }

    public function testFindByNameWhenNotExists(): void
    {
        $foundConfig = $this->repository->findByName('non-existent-name');

        $this->assertNull($foundConfig);
    }

    public function testFindByNameReturnsFirstMatch(): void
    {
        // 由于name字段有唯一约束，这个测试实际上是无效的
        // 我们改为测试findByName方法的正确行为
        $config = $this->createNewEntity();
        $config->setName('unique-test-name');
        $this->repository->save($config, true);

        $foundConfig = $this->repository->findByName('unique-test-name');

        $this->assertNotNull($foundConfig);
        $this->assertSame('unique-test-name', $foundConfig->getName());
        $this->assertSame($config->getId(), $foundConfig->getId());
    }
}
