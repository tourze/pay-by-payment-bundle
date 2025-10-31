<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Service\PayByConfigManager;

#[AsCommand(
    name: 'payby:config:list',
    description: '列出所有 PayBy 支付配置'
)]
#[Autoconfigure(public: true)]
class PayByConfigListCommand extends Command
{
    public function __construct(
        private readonly PayByConfigManager $configManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('PayBy 支付配置列表');

        $configs = $this->configManager->getAllEnabledConfigs();

        if (0 === count($configs)) {
            $io->warning('没有找到启用的 PayBy 配置');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table->setHeaders(['名称', '描述', 'API 地址', '商户ID', '超时', '重试次数', '是否默认']);
        $table->setStyle('box');

        foreach ($configs as $config) {
            $table->addRow([
                $config->getName(),
                $config->getDescription(),
                $config->getApiBaseUrl(),
                $config->getMerchantId(),
                $config->getTimeout() . 's',
                $config->getRetryAttempts(),
                $config->isDefault() ? '✓' : '✗',
            ]);
        }

        $table->render();

        $io->success(sprintf('共找到 %d 个 PayBy 配置', count($configs)));

        return Command::SUCCESS;
    }
}
