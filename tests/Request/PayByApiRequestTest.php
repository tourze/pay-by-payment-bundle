<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Request;

use HttpClientBundle\Request\RequestInterface;
use HttpClientBundle\Tests\Request\RequestTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PayByPaymentBundle\Request\PayByApiRequest;

/**
 * @internal
 */
#[CoversClass(PayByApiRequest::class)]
final class PayByApiRequestTest extends RequestTestCase
{
    public function testConstructorAndBasicGetters(): void
    {
        $method = 'POST';
        $url = 'https://api.payby.com/v1/orders';
        $data = ['key' => 'value'];
        $headers = ['Content-Type' => 'application/json'];
        $timeout = 30;

        $request = new PayByApiRequest($method, $url, $data, $headers, $timeout);

        $this->assertEquals($method, $request->getRequestMethod());
        $this->assertEquals($url, $request->getUrl());
        $this->assertEquals($data, $request->getData());
        $this->assertEquals($headers, $request->getHeaders());
        $this->assertEquals($timeout, $request->getTimeout());
    }

    public function testConstructorWithDefaults(): void
    {
        $method = 'GET';
        $url = 'https://api.payby.com/v1/orders/123';

        $request = new PayByApiRequest($method, $url);

        $this->assertEquals($method, $request->getRequestMethod());
        $this->assertEquals($url, $request->getUrl());
        $this->assertEquals([], $request->getData());
        $this->assertEquals([], $request->getHeaders());
        $this->assertNull($request->getTimeout());
    }

    public function testImplementsRequestInterface(): void
    {
        $request = new PayByApiRequest('GET', 'https://example.com');

        $this->assertInstanceOf(RequestInterface::class, $request);
    }

    public function testGetRequestPath(): void
    {
        $testCases = [
            'https://api.payby.com/v1/orders' => '/v1/orders',
            'https://api.payby.com/v1/orders/123' => '/v1/orders/123',
            'https://api.payby.com/' => '/',
            'https://api.payby.com' => '/',
            'https://api.payby.com/path/to/resource?param=value' => '/path/to/resource',
        ];

        foreach ($testCases as $url => $expectedPath) {
            $request = new PayByApiRequest('GET', $url);
            $this->assertEquals($expectedPath, $request->getRequestPath());
        }
    }

    public function testGetRequestOptionsWithEmptyData(): void
    {
        $headers = ['Authorization' => 'Bearer token'];
        $timeout = 45;

        $request = new PayByApiRequest('GET', 'https://example.com', [], $headers, $timeout);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertArrayNotHasKey('json', $options);
        $this->assertEquals($headers, $options['headers']);
        $this->assertEquals($timeout, $options['timeout']);
    }

    public function testGetRequestOptionsWithData(): void
    {
        $data = ['merchantOrderNo' => 'ORDER-123', 'amount' => '100.00'];
        $headers = ['Content-Type' => 'application/json'];
        $timeout = 60;

        $request = new PayByApiRequest('POST', 'https://api.example.com', $data, $headers, $timeout);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertEquals($data, $options['json']);
        $this->assertEquals($headers, $options['headers']);
        $this->assertEquals($timeout, $options['timeout']);
    }

    public function testGetRequestOptionsWithEmptyParameters(): void
    {
        $request = new PayByApiRequest('DELETE', 'https://api.example.com/resource/123');
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertEmpty($options);
    }

    public function testGetRequestOptionsWithNullTimeout(): void
    {
        $data = ['test' => 'value'];
        $headers = ['Accept' => 'application/json'];

        $request = new PayByApiRequest('PATCH', 'https://example.com', $data, $headers, null);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertEquals($data, $options['json']);
        $this->assertEquals($headers, $options['headers']);
        $this->assertArrayNotHasKey('timeout', $options);
    }

    public function testGetRequestOptionsWithComplexData(): void
    {
        $complexData = [
            'merchantOrderNo' => 'COMPLEX-ORDER-456',
            'totalAmount' => [
                'amount' => '250.75',
                'currency' => 'USD',
            ],
            'accessoryContent' => [
                'metadata' => [
                    'customerId' => 'CUST-789',
                    'tags' => ['premium', 'recurring'],
                ],
            ],
        ];

        $request = new PayByApiRequest('POST', 'https://api.payby.com/orders', $complexData);
        $options = $request->getRequestOptions();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('json', $options);
        $this->assertEquals($complexData, $options['json']);
    }

    public function testDifferentHttpMethods(): void
    {
        $methods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'];

        foreach ($methods as $method) {
            $request = new PayByApiRequest($method, 'https://example.com');
            $this->assertEquals($method, $request->getRequestMethod());
        }
    }
}
