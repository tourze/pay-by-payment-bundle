<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Controller\Admin;

use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Tourze\PayByPaymentBundle\Controller\Admin\PayByConfigCrudController;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(PayByConfigCrudController::class)]
#[RunTestsInSeparateProcesses]
class PayByConfigCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function afterEasyAdminSetUp(): void
    {
        // 确保测试数据存在
        $container = self::getContainer();
        /** @var ObjectManager $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        // 检查是否已有数据，如果没有则创建测试数据
        $repository = $entityManager->getRepository(PayByConfig::class);
        $existingConfigs = method_exists($repository, 'count') ? $repository->count() : count($repository->findAll());
        if (0 === $existingConfigs) {
            $config = new PayByConfig();
            $config->setName('test_config');
            $config->setDescription('Test payment configuration');
            $config->setApiBaseUrl('https://api.test.payby.com');
            $config->setApiKey('test_api_key');
            $config->setApiSecret('test_api_secret');
            $config->setMerchantId('test_merchant_id');
            $config->setCallbackUrl('https://example.com/callback');
            $config->setTimeout(30);
            $config->setRetryAttempts(3);
            $config->setEnabled(true);
            $config->setDefault(true);

            $entityManager->persist($config);
            $entityManager->flush();
        }
    }

    protected function getControllerService(): PayByConfigCrudController
    {
        return new PayByConfigCrudController();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '配置名称' => ['配置名称'];
        yield '配置描述' => ['配置描述'];
        yield 'API基础地址' => ['API基础地址'];
        yield '商户ID' => ['商户ID'];
        yield '回调地址' => ['回调地址'];
        yield '超时时间（秒）' => ['超时时间（秒）'];
        yield '重试次数' => ['重试次数'];
        yield '是否默认配置' => ['是否默认配置'];
        yield '是否启用' => ['是否启用'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'apiBaseUrl' => ['apiBaseUrl'];
        yield 'apiKey' => ['apiKey'];
        yield 'apiSecret' => ['apiSecret'];
        yield 'merchantId' => ['merchantId'];
        yield 'callbackUrl' => ['callbackUrl'];
        yield 'timeout' => ['timeout'];
        yield 'retryAttempts' => ['retryAttempts'];
        yield 'isDefault' => ['isDefault'];
        yield 'extraConfig' => ['extraConfig'];
        yield 'enabled' => ['enabled'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function provideEditPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'apiBaseUrl' => ['apiBaseUrl'];
        yield 'apiKey' => ['apiKey'];
        yield 'apiSecret' => ['apiSecret'];
        yield 'merchantId' => ['merchantId'];
        yield 'callbackUrl' => ['callbackUrl'];
        yield 'timeout' => ['timeout'];
        yield 'retryAttempts' => ['retryAttempts'];
        yield 'isDefault' => ['isDefault'];
        yield 'extraConfig' => ['extraConfig'];
        yield 'enabled' => ['enabled'];
    }

    public function testEntityFqcn(): void
    {
        self::assertSame(PayByConfig::class, PayByConfigCrudController::getEntityFqcn());
    }

    public function testValidationErrors(): void
    {
        $validator = self::getService(ValidatorInterface::class);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $entity = new PayByConfig();
        $violations = $validator->validate($entity);

        self::assertGreaterThan(0, $violations->count());

        $violationMessages = [];
        foreach ($violations as $violation) {
            $violationMessages[] = $violation->getMessage();
        }

        self::assertContains('This value should not be blank.', $violationMessages);
    }
}
