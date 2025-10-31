<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Service;

use HttpClientBundle\Client\ApiClient;
use HttpClientBundle\Request\RequestInterface;
use Monolog\Attribute\WithMonologChannel;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PayByPaymentBundle\Entity\PayByConfig;
use Tourze\PayByPaymentBundle\Exception\PayByApiException;
use Tourze\PayByPaymentBundle\Request\CreateOrderRequest;
use Tourze\PayByPaymentBundle\Request\PayByApiRequest;

#[Autoconfigure(public: true)]
#[WithMonologChannel(channel: 'pay_by_payment')]
class PayByApiClient
{
    public function __construct(
        private ?ApiClient $apiClient,
        private PayBySignatureService $signatureService,
        private PayByConfigManager $configManager,
        private LoggerInterface $logger,
        ?string $configName = null,
    ) {
        $this->configName = $configName ?? 'default';
        $this->loadConfig();
    }

    private string $configName;

    private ?PayByConfig $config = null;

    private string $apiBaseUrl;

    private string $apiVersion = 'v1';

    private int $timeout;

    private function loadConfig(): void
    {
        $this->config = $this->configManager->getConfig($this->configName);
        if (null === $this->config) {
            $this->config = $this->configManager->getDefaultConfig();
        }

        if (null === $this->config) {
            throw new PayByApiException("No PayBy configuration found for '{$this->configName}'", 'CONFIG_ERROR');
        }

        $this->apiBaseUrl = $this->config->getApiBaseUrl();
        $this->timeout = $this->config->getTimeout();
    }

    public function setConfig(string $configName): void
    {
        $this->configName = $configName;
        $this->loadConfig();
    }

    /**
     * @return array<string, mixed>
     */
    public function createOrder(CreateOrderRequest $request): array
    {
        $data = $request->toArray();

        return $this->post('/orders', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryOrder(string $orderId): array
    {
        return $this->get('/orders/' . $orderId);
    }

    /**
     * @return array<string, mixed>
     */
    public function cancelOrder(string $orderId, string $cancelReason = ''): array
    {
        $data = [];
        if ('' !== $cancelReason) {
            $data['cancelReason'] = $cancelReason;
        }

        return $this->post('/orders/' . $orderId . '/cancel', $data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createRefund(array $data): array
    {
        return $this->post('/refunds', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryRefund(string $refundId): array
    {
        return $this->get('/refunds/' . $refundId);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createTransfer(array $data): array
    {
        return $this->post('/transfers', $data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    public function createBankTransfer(array $data): array
    {
        return $this->post('/transfers/bank', $data);
    }

    /**
     * @return array<string, mixed>
     */
    public function queryTransfer(string $transferId): array
    {
        return $this->get('/transfers/' . $transferId);
    }

    /**
     * @return array<string, mixed>
     */
    private function get(string $path): array
    {
        return $this->request('GET', $path);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function post(string $path, array $data = []): array
    {
        return $this->request('POST', $path, $data);
    }

    /**
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, array $data = []): array
    {
        $url = $this->apiBaseUrl . '/api/' . $this->apiVersion . $path;

        $this->logger->info('Making PayBy API request', [
            'method' => $method,
            'url' => $url,
            'data' => $data,
            'config' => $this->config?->getName() ?? 'unknown',
        ]);

        $options = $this->buildRequestOptions($method, $data);

        try {
            if (null === $this->apiClient) {
                throw new PayByApiException('ApiClient not configured for test environment', 'TEST_CONFIG_ERROR');
            }

            $apiRequest = new PayByApiRequest(
                $method,
                $url,
                $options['json'] ?? [],
                $options['headers'],
                $options['timeout']
            );

            $response = $this->apiClient->request($apiRequest);

            return $this->processResponse($response);
        } catch (\Exception $e) {
            $this->handleRequestError($e);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @return array{timeout: int, headers: array<string, string>, json?: array<string, mixed>}
     */
    private function buildRequestOptions(string $method, array $data): array
    {
        /** @var array<string, string> $headers */
        $headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ];

        if ('POST' === $method && count($data) > 0) {
            $signatureData = $this->signatureService->generateSignature($data);
            $headers['X-PayBy-Signature'] = (string) $signatureData['signature'];
            $headers['X-PayBy-Timestamp'] = (string) $signatureData['timestamp'];

            return [
                'timeout' => $this->timeout,
                'headers' => $headers,
                'json' => $data,
            ];
        }

        if ('GET' === $method) {
            $signatureData = $this->signatureService->generateSignature([]);
            $headers['X-PayBy-Signature'] = (string) $signatureData['signature'];
            $headers['X-PayBy-Timestamp'] = (string) $signatureData['timestamp'];
        }

        return [
            'timeout' => $this->timeout,
            'headers' => $headers,
        ];
    }

    /**
     * @param mixed $response
     * @return array<string, mixed>
     */
    private function processResponse($response): array
    {
        // 验证响应对象类型
        if (!is_object($response) || !method_exists($response, 'toArray') || !method_exists($response, 'getStatusCode')) {
            throw new PayByApiException('Invalid response object', 'INVALID_RESPONSE');
        }

        $responseData = $response->toArray();
        $statusCode = $response->getStatusCode();

        // 验证响应数据为数组
        if (!is_array($responseData)) {
            throw new PayByApiException('Invalid response format', 'INVALID_FORMAT');
        }

        $this->logger->info('PayBy API response received', [
            'status_code' => $statusCode,
            'response' => $responseData,
        ]);

        return $this->extractResponseData($responseData);
    }

    /**
     * @param array<mixed, mixed> $responseData
     * @return array<string, mixed>
     */
    private function extractResponseData(array $responseData): array
    {
        // 验证并提取响应代码
        $codeValue = $responseData['code'] ?? 'UNKNOWN_ERROR';
        $code = is_string($codeValue) ? $codeValue : 'UNKNOWN_ERROR';

        if ('SUCCESS' !== $code) {
            $messageValue = $responseData['message'] ?? 'API request failed';
            $message = is_string($messageValue) ? $messageValue : 'API request failed';

            /** @var array<string, mixed> $safeResponseData */
            $safeResponseData = $responseData;
            throw new PayByApiException($message, $code, $safeResponseData);
        }

        // 验证并提取数据字段
        $dataValue = $responseData['data'] ?? [];
        if (!is_array($dataValue)) {
            throw new PayByApiException('Invalid data format', 'INVALID_DATA');
        }

        /** @var array<string, mixed> */
        return $dataValue;
    }

    /**
     * @return never
     */
    private function handleRequestError(\Exception $e): never
    {
        $this->logger->error('PayBy API request failed', [
            'error' => $e->getMessage(),
            'exception' => get_class($e),
            'config' => $this->config?->getName() ?? 'unknown',
        ]);

        if ($e instanceof PayByApiException) {
            throw $e;
        }

        throw new PayByApiException('Request failed: ' . $e->getMessage(), 'REQUEST_ERROR');
    }
}
