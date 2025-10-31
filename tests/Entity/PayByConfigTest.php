<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(PayByConfig::class)]
class PayByConfigTest extends AbstractEntityTestCase
{
    private PayByConfig $config;

    protected function setUp(): void
    {
        $this->config = $this->createEntity();
    }

    protected function createEntity(): PayByConfig
    {
        return new PayByConfig();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $properties = [
            'code' => 'test_code',
            'partnerName' => 'Test Partner',
            'signingAlgorithm' => 'RSA256',
            'enabled' => true,
        ];

        foreach ($properties as $property => $sampleValue) {
            yield $property => [$property, $sampleValue];
        }
    }

    public function testConstructor(): void
    {
        $config = new PayByConfig();

        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getCreateTime());
        $this->assertInstanceOf(\DateTimeImmutable::class, $config->getUpdateTime());
        $this->assertTrue($config->isEnabled());
        $this->assertSame(30, $config->getTimeout());
        $this->assertSame(3, $config->getRetryAttempts());
        $this->assertSame([], $config->getExtraConfig());
        $this->assertFalse($config->isDefault());
    }

    public function testNameGetterAndSetter(): void
    {
        $name = 'test-config';
        $this->config->setName($name);

        $this->assertSame($name, $this->config->getName());
    }

    public function testDescriptionGetterAndSetter(): void
    {
        $description = 'Test PayBy configuration';
        $this->config->setDescription($description);

        $this->assertSame($description, $this->config->getDescription());
    }

    public function testApiBaseUrlGetterAndSetter(): void
    {
        $apiBaseUrl = 'https://api.payby.com';
        $this->config->setApiBaseUrl($apiBaseUrl);

        $this->assertSame($apiBaseUrl, $this->config->getApiBaseUrl());
    }

    public function testApiKeyGetterAndSetter(): void
    {
        $apiKey = 'test-api-key';
        $this->config->setApiKey($apiKey);

        $this->assertSame($apiKey, $this->config->getApiKey());
    }

    public function testApiSecretGetterAndSetter(): void
    {
        $apiSecret = 'test-api-secret';
        $this->config->setApiSecret($apiSecret);

        $this->assertSame($apiSecret, $this->config->getApiSecret());
    }

    public function testMerchantIdGetterAndSetter(): void
    {
        $merchantId = 'test-merchant-id';
        $this->config->setMerchantId($merchantId);

        $this->assertSame($merchantId, $this->config->getMerchantId());
    }

    public function testCallbackUrlGetterAndSetter(): void
    {
        $callbackUrl = 'https://example.com/callback';
        $this->config->setCallbackUrl($callbackUrl);

        $this->assertSame($callbackUrl, $this->config->getCallbackUrl());
    }

    public function testCallbackUrlWithNull(): void
    {
        $this->config->setCallbackUrl(null);

        $this->assertNull($this->config->getCallbackUrl());
    }

    public function testEnabledGetterAndSetter(): void
    {
        $this->config->setEnabled(false);

        $this->assertFalse($this->config->isEnabled());

        $this->config->setEnabled(true);
        $this->assertTrue($this->config->isEnabled());
    }

    public function testTimeoutGetterAndSetter(): void
    {
        $timeout = 60;
        $this->config->setTimeout($timeout);

        $this->assertSame($timeout, $this->config->getTimeout());
    }

    public function testRetryAttemptsGetterAndSetter(): void
    {
        $retryAttempts = 5;
        $this->config->setRetryAttempts($retryAttempts);

        $this->assertSame($retryAttempts, $this->config->getRetryAttempts());
    }

    public function testExtraConfigGetterAndSetter(): void
    {
        $extraConfig = ['key' => 'value', 'enabled' => true];
        $this->config->setExtraConfig($extraConfig);

        $this->assertSame($extraConfig, $this->config->getExtraConfig());
    }

    public function testExtraConfigWithNull(): void
    {
        $this->config->setExtraConfig(null);

        $this->assertNull($this->config->getExtraConfig());
    }

    public function testDefaultGetterAndSetter(): void
    {
        $this->config->setDefault(true);

        $this->assertTrue($this->config->isDefault());

        $this->config->setDefault(false);
        $this->assertFalse($this->config->isDefault());
    }

    public function testCreateTimeGetterAndSetter(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');
        $this->config->setCreateTime($createTime);

        $this->assertSame($createTime, $this->config->getCreateTime());
    }

    public function testUpdateTimeGetterAndSetter(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 12:00:00');
        $this->config->setUpdateTime($updateTime);

        $this->assertSame($updateTime, $this->config->getUpdateTime());
    }

    public function testDeprecatedCreatedAtMethods(): void
    {
        $createTime = new \DateTimeImmutable('2023-01-01 10:00:00');

        $this->config->setCreateTime($createTime);
        $this->assertSame($createTime, $this->config->getCreateTime());
    }

    public function testDeprecatedUpdatedAtMethods(): void
    {
        $updateTime = new \DateTimeImmutable('2023-01-01 12:00:00');

        $this->config->setUpdateTime($updateTime);
        $this->assertSame($updateTime, $this->config->getUpdateTime());
        $this->assertSame($updateTime, $this->config->getUpdateTime());
    }

    public function testToString(): void
    {
        $name = 'production-config';
        $this->config->setName($name);

        $this->assertSame($name, (string) $this->config);
        $this->assertSame($name, $this->config->__toString());
    }
}
