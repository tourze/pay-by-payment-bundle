<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Request;

use HttpClientBundle\Request\RequestInterface;

class PayByApiRequest implements RequestInterface
{
    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $headers
     */
    public function __construct(
        private readonly string $method,
        private readonly string $url,
        private readonly array $data = [],
        private readonly array $headers = [],
        private readonly ?int $timeout = null,
    ) {
    }

    public function getRequestMethod(): ?string
    {
        return $this->method;
    }

    public function getRequestPath(): string
    {
        // 返回 URL 的路径部分
        $parsedUrl = parse_url($this->url);

        return $parsedUrl['path'] ?? '/';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRequestOptions(): ?array
    {
        $options = [];

        if (count($this->data) > 0) {
            $options['json'] = $this->data;
        }

        if (count($this->headers) > 0) {
            $options['headers'] = $this->headers;
        }

        if (null !== $this->timeout) {
            $options['timeout'] = $this->timeout;
        }

        return $options;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getTimeout(): ?int
    {
        return $this->timeout;
    }
}
