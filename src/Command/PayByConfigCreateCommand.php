<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Service\PayByConfigManager;

#[AsCommand(
    name: 'payby:config:create',
    description: '创建新的 PayBy 支付配置'
)]
#[Autoconfigure(public: true)]
class PayByConfigCreateCommand extends Command
{
    public function __construct(
        private readonly PayByConfigManager $configManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::REQUIRED, '配置名称')
            ->addArgument('apiBaseUrl', InputArgument::REQUIRED, 'API 地址')
            ->addArgument('apiKey', InputArgument::REQUIRED, 'API 密钥')
            ->addArgument('apiSecret', InputArgument::REQUIRED, 'API 密钥')
            ->addArgument('merchantId', InputArgument::REQUIRED, '商户ID')
            ->addOption('description', 'd', InputOption::VALUE_OPTIONAL, '配置描述', '')
            ->addOption('timeout', 't', InputOption::VALUE_OPTIONAL, '超时时间（秒）', 30)
            ->addOption('retry-attempts', 'r', InputOption::VALUE_OPTIONAL, '重试次数', 3)
            ->addOption('default', null, InputOption::VALUE_NONE, '设为默认配置')
            ->addOption('extra', null, InputOption::VALUE_OPTIONAL, '额外配置（JSON格式）')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $timeout = $input->getOption('timeout');
        $retryAttempts = $input->getOption('retry-attempts');

        $data = [
            'name' => $input->getArgument('name'),
            'description' => $input->getOption('description'),
            'apiBaseUrl' => $input->getArgument('apiBaseUrl'),
            'apiKey' => $input->getArgument('apiKey'),
            'apiSecret' => $input->getArgument('apiSecret'),
            'merchantId' => $input->getArgument('merchantId'),
            'timeout' => is_numeric($timeout) ? (int) $timeout : 30,
            'retryAttempts' => is_numeric($retryAttempts) ? (int) $retryAttempts : 3,
            'isDefault' => $input->getOption('default'),
        ];

        $extraConfig = $input->getOption('extra');
        if (null !== $extraConfig && is_string($extraConfig)) {
            $decoded = json_decode($extraConfig, true);
            if (JSON_ERROR_NONE !== json_last_error()) {
                $io->error('额外配置必须是有效的 JSON 格式');

                return Command::FAILURE;
            }
            $data['extraConfig'] = $decoded;
        }

        try {
            $config = $this->configManager->createConfig($data);

            $io->success('PayBy 配置创建成功！');
            $io->table(['属性', '值'], [
                ['ID', $config->getId()],
                ['名称', $config->getName()],
                ['描述', $config->getDescription()],
                ['API 地址', $config->getApiBaseUrl()],
                ['商户ID', $config->getMerchantId()],
                ['超时', $config->getTimeout() . 's'],
                ['重试次数', $config->getRetryAttempts()],
                ['是否默认', $config->isDefault() ? '是' : '否'],
            ]);

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('创建配置失败：' . $e->getMessage());

            return Command::FAILURE;
        }
    }
}
