<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;

class PayByConfigFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // 在测试环境中不加载Fixtures，避免干扰单元测试
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? null;
        if ('test' === $env) {
            return;
        }

        $config = new PayByConfig();
        $config->setName('test-config');
        $config->setDescription('测试支付配置');
        $config->setApiBaseUrl('https://api.test.payby.com');
        $config->setMerchantId('test_merchant_001');
        $config->setApiKey('test_api_key');
        $config->setApiSecret('test_api_secret');
        $config->setEnabled(false);
        $config->setDefault(false);

        $manager->persist($config);
        $manager->flush();
    }
}
