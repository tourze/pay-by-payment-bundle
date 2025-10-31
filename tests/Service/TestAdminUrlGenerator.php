<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

/**
 * 测试环境下的 AdminUrlGenerator 包装器
 * 自动设置 PayByDashboard 以避免 setDashboard() 错误
 */
final class TestAdminUrlGenerator
{
    private AdminUrlGenerator $decorated;

    private string $dashboardClass;

    private bool $dashboardSet = false;

    public function __construct(AdminUrlGenerator $decorated, string $dashboardClass)
    {
        $this->decorated = $decorated;
        $this->dashboardClass = $dashboardClass;
    }

    public function setDashboard(string $dashboardControllerFqcn): void
    {
        $this->decorated->setDashboard($dashboardControllerFqcn);
        $this->dashboardSet = true;
    }

    public function setController(string $crudControllerFqcn): void
    {
        $this->decorated->setController($crudControllerFqcn);
    }

    public function setAction(string $action): void
    {
        $this->decorated->setAction($action);
    }

    public function setEntityId(mixed $entityId): void
    {
        $this->decorated->setEntityId($entityId);
    }

    public function set(string $paramName, mixed $paramValue): void
    {
        $this->decorated->set($paramName, $paramValue);
    }

    /**
     * @param array<string, mixed> $params
     */
    public function setAll(array $params): void
    {
        $this->decorated->setAll($params);
    }

    public function unset(string $paramName): self
    {
        $this->decorated->unset($paramName);

        return $this;
    }

    public function unsetAll(): self
    {
        $this->decorated->unsetAll();
        $this->dashboardSet = false;

        return $this;
    }

    public function get(string $paramName): mixed
    {
        return $this->decorated->get($paramName);
    }

    /**
     * @return array<string, mixed>
     */
    public function getAll(): array
    {
        // AdminUrlGenerator没有getAll()方法，返回空数组
        return [];
    }

    public function generateUrl(): string
    {
        // 如果未设置Dashboard，自动设置默认的PayByDashboard
        if (!$this->dashboardSet) {
            $this->decorated->setDashboard($this->dashboardClass);
        }

        return $this->decorated->generateUrl();
    }

    public function __clone()
    {
        $this->decorated = clone $this->decorated;
    }
}
