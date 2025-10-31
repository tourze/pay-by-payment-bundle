<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PayByPaymentBundle\Command\PayByConfigListCommand;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Service\PayByConfigManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;

/**
 * @internal
 */
#[CoversClass(PayByConfigListCommand::class)]
#[RunTestsInSeparateProcesses]
final class PayByConfigListCommandTest extends AbstractCommandTestCase
{
    private CommandTester $commandTester;

    /** @var PayByConfigManager&MockObject */
    private PayByConfigManager $configManager;

    protected function onSetUp(): void
    {
        /** @var PayByConfigManager&MockObject $configManager */
        $configManager = $this->createMock(PayByConfigManager::class);
        $this->configManager = $configManager;
        self::getContainer()->set(PayByConfigManager::class, $this->configManager);

        /** @var PayByConfigListCommand $command */
        $command = self::getContainer()->get(PayByConfigListCommand::class);
        $this->commandTester = new CommandTester($command);
    }

    protected function getCommandTester(): CommandTester
    {
        return $this->commandTester;
    }

    public function testCommandCanBeInstantiated(): void
    {
        $command = self::getContainer()->get(PayByConfigListCommand::class);

        $this->assertInstanceOf(PayByConfigListCommand::class, $command);
    }

    public function testListConfigsWithData(): void
    {
        $config1 = new PayByConfig();
        $config1->setName('test-config-1');
        $config1->setDescription('第一个测试配置');
        $config1->setApiBaseUrl('https://api1.test.com');
        $config1->setApiKey('test-key-1');
        $config1->setApiSecret('test-secret-1');
        $config1->setMerchantId('merchant-001');
        $config1->setTimeout(30);
        $config1->setRetryAttempts(3);
        $config1->setEnabled(true);
        $config1->setDefault(true);

        $config2 = new PayByConfig();
        $config2->setName('test-config-2');
        $config2->setDescription('第二个测试配置');
        $config2->setApiBaseUrl('https://api2.test.com');
        $config2->setApiKey('test-key-2');
        $config2->setApiSecret('test-secret-2');
        $config2->setMerchantId('merchant-002');
        $config2->setTimeout(60);
        $config2->setRetryAttempts(5);
        $config2->setEnabled(true);
        $config2->setDefault(false);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$config1, $config2])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('PayBy 支付配置列表', $output);
        $this->assertStringContainsString('共找到 2 个 PayBy 配置', $output);

        $this->assertStringContainsString('test-config-1', $output);
        $this->assertStringContainsString('第一个测试配置', $output);
        $this->assertStringContainsString('https://api1.test.com', $output);
        $this->assertStringContainsString('merchant-001', $output);
        $this->assertStringContainsString('30s', $output);
        $this->assertStringContainsString('3', $output);
        $this->assertStringContainsString('✓', $output);

        $this->assertStringContainsString('test-config-2', $output);
        $this->assertStringContainsString('第二个测试配置', $output);
        $this->assertStringContainsString('https://api2.test.com', $output);
        $this->assertStringContainsString('merchant-002', $output);
        $this->assertStringContainsString('60s', $output);
        $this->assertStringContainsString('5', $output);
        $this->assertStringContainsString('✗', $output);
    }

    public function testListConfigsOutputFormat(): void
    {
        $config = new PayByConfig();
        $config->setName('format-test');
        $config->setDescription('格式测试');
        $config->setApiBaseUrl('https://api.format.com');
        $config->setApiKey('format-key');
        $config->setApiSecret('format-secret');
        $config->setMerchantId('format-merchant');
        $config->setTimeout(45);
        $config->setRetryAttempts(2);
        $config->setEnabled(true);
        $config->setDefault(false);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$config])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('┌─', $output);
        $this->assertStringContainsString('├─', $output);
        $this->assertStringContainsString('└─', $output);

        $this->assertStringContainsString('名称', $output);
        $this->assertStringContainsString('描述', $output);
        $this->assertStringContainsString('API 地址', $output);
        $this->assertStringContainsString('商户ID', $output);
        $this->assertStringContainsString('超时', $output);
        $this->assertStringContainsString('重试次数', $output);
        $this->assertStringContainsString('是否默认', $output);
    }

    public function testListConfigsWhenNoEnabledConfigs(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('PayBy 支付配置列表', $output);
        $this->assertStringContainsString('没有找到启用的 PayBy 配置', $output);

        $this->assertStringNotContainsString('共找到', $output);
    }

    public function testListConfigsWithMixedDefaultStatus(): void
    {
        $defaultConfig = new PayByConfig();
        $defaultConfig->setName('default-config');
        $defaultConfig->setDescription('默认配置');
        $defaultConfig->setApiBaseUrl('https://default.test.com');
        $defaultConfig->setApiKey('default-key');
        $defaultConfig->setApiSecret('default-secret');
        $defaultConfig->setMerchantId('default-merchant');
        $defaultConfig->setTimeout(30);
        $defaultConfig->setRetryAttempts(3);
        $defaultConfig->setEnabled(true);
        $defaultConfig->setDefault(true);

        $nonDefaultConfig = new PayByConfig();
        $nonDefaultConfig->setName('non-default-config');
        $nonDefaultConfig->setDescription('非默认配置');
        $nonDefaultConfig->setApiBaseUrl('https://non-default.test.com');
        $nonDefaultConfig->setApiKey('non-default-key');
        $nonDefaultConfig->setApiSecret('non-default-secret');
        $nonDefaultConfig->setMerchantId('non-default-merchant');
        $nonDefaultConfig->setTimeout(60);
        $nonDefaultConfig->setRetryAttempts(5);
        $nonDefaultConfig->setEnabled(true);
        $nonDefaultConfig->setDefault(false);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$defaultConfig, $nonDefaultConfig])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();

        $lines = explode("\n", $output);
        $configLines = array_filter($lines, function ($line) {
            return str_contains($line, 'config');
        });

        $this->assertNotEmpty($configLines);

        $defaultFound = false;
        $nonDefaultFound = false;

        foreach ($configLines as $line) {
            if (str_contains($line, '✓')) {
                $defaultFound = true;
            }
            if (str_contains($line, '✗')) {
                $nonDefaultFound = true;
            }
        }

        $this->assertTrue($defaultFound, '应该有一个默认配置');
        $this->assertTrue($nonDefaultFound, '应该有一个非默认配置');
    }

    public function testListConfigsShowsCorrectTimeout(): void
    {
        $shortTimeoutConfig = new PayByConfig();
        $shortTimeoutConfig->setName('short-timeout');
        $shortTimeoutConfig->setDescription('短超时配置');
        $shortTimeoutConfig->setApiBaseUrl('https://short.test.com');
        $shortTimeoutConfig->setApiKey('short-key');
        $shortTimeoutConfig->setApiSecret('short-secret');
        $shortTimeoutConfig->setMerchantId('short-merchant');
        $shortTimeoutConfig->setTimeout(30);
        $shortTimeoutConfig->setRetryAttempts(3);
        $shortTimeoutConfig->setEnabled(true);
        $shortTimeoutConfig->setDefault(false);

        $longTimeoutConfig = new PayByConfig();
        $longTimeoutConfig->setName('long-timeout');
        $longTimeoutConfig->setDescription('长超时配置');
        $longTimeoutConfig->setApiBaseUrl('https://long.test.com');
        $longTimeoutConfig->setApiKey('long-key');
        $longTimeoutConfig->setApiSecret('long-secret');
        $longTimeoutConfig->setMerchantId('long-merchant');
        $longTimeoutConfig->setTimeout(60);
        $longTimeoutConfig->setRetryAttempts(3);
        $longTimeoutConfig->setEnabled(true);
        $longTimeoutConfig->setDefault(false);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$shortTimeoutConfig, $longTimeoutConfig])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();

        $this->assertMatchesRegularExpression('/30s/', $output);
        $this->assertMatchesRegularExpression('/60s/', $output);
    }

    public function testListConfigsShowsCorrectRetryAttempts(): void
    {
        $lowRetryConfig = new PayByConfig();
        $lowRetryConfig->setName('low-retry');
        $lowRetryConfig->setDescription('低重试配置');
        $lowRetryConfig->setApiBaseUrl('https://low.test.com');
        $lowRetryConfig->setApiKey('low-key');
        $lowRetryConfig->setApiSecret('low-secret');
        $lowRetryConfig->setMerchantId('low-merchant');
        $lowRetryConfig->setTimeout(30);
        $lowRetryConfig->setRetryAttempts(3);
        $lowRetryConfig->setEnabled(true);
        $lowRetryConfig->setDefault(false);

        $highRetryConfig = new PayByConfig();
        $highRetryConfig->setName('high-retry');
        $highRetryConfig->setDescription('高重试配置');
        $highRetryConfig->setApiBaseUrl('https://high.test.com');
        $highRetryConfig->setApiKey('high-key');
        $highRetryConfig->setApiSecret('high-secret');
        $highRetryConfig->setMerchantId('high-merchant');
        $highRetryConfig->setTimeout(60);
        $highRetryConfig->setRetryAttempts(5);
        $highRetryConfig->setEnabled(true);
        $highRetryConfig->setDefault(false);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$lowRetryConfig, $highRetryConfig])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();

        $this->assertStringContainsString('3', $output);
        $this->assertStringContainsString('5', $output);
    }

    public function testListConfigsWithLongDescriptions(): void
    {
        $config = new PayByConfig();
        $config->setName('long-desc-config');
        $config->setDescription('这是一个非常长的描述文本，用来测试表格在处理长文本时的显示效果');
        $config->setApiBaseUrl('https://long.test.com');
        $config->setApiKey('long-key');
        $config->setApiSecret('long-secret');
        $config->setMerchantId('long-merchant');
        $config->setTimeout(120);
        $config->setRetryAttempts(7);
        $config->setEnabled(true);
        $config->setDefault(false);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$config])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('long-desc-config', $output);
        $this->assertStringContainsString('这是一个非常长的描述文本', $output);
        $this->assertStringContainsString('共找到 1 个 PayBy 配置', $output);
    }

    public function testListConfigsWithSingleConfig(): void
    {
        $config = new PayByConfig();
        $config->setName('single-config');
        $config->setDescription('单一配置');
        $config->setApiBaseUrl('https://single.test.com');
        $config->setApiKey('single-key');
        $config->setApiSecret('single-secret');
        $config->setMerchantId('single-merchant');
        $config->setTimeout(45);
        $config->setRetryAttempts(4);
        $config->setEnabled(true);
        $config->setDefault(true);

        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willReturn([$config])
        ;

        $exitCode = $this->commandTester->execute([]);

        $this->assertEquals(Command::SUCCESS, $exitCode);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('PayBy 支付配置列表', $output);
        $this->assertStringContainsString('single-config', $output);
        $this->assertStringContainsString('共找到 1 个 PayBy 配置', $output);
        $this->assertStringContainsString('✓', $output);
    }

    public function testListConfigsWithServiceException(): void
    {
        $this->configManager
            ->expects($this->once())
            ->method('getAllEnabledConfigs')
            ->willThrowException(new \Exception('数据库连接失败'))
        ;

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('数据库连接失败');

        $this->commandTester->execute([]);
    }
}
