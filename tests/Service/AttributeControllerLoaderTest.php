<?php

declare(strict_types=1);

namespace Tourze\PayByPaymentBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PayByPaymentBundle\Service\AttributeControllerLoader;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    private AttributeControllerLoader $loader;

    protected function onSetUp(): void
    {
        $this->loader = self::getService(AttributeControllerLoader::class);
    }

    public function testImplementsLoaderInterface(): void
    {
        $this->assertInstanceOf(LoaderInterface::class, $this->loader);
    }

    public function testImplementsRoutingAutoLoaderInterface(): void
    {
        $this->assertInstanceOf(RoutingAutoLoaderInterface::class, $this->loader);
    }

    public function testLoadCallsAutoload(): void
    {
        $resource = 'test-resource';
        $type = 'test-type';

        $result = $this->loader->load($resource, $type);

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testSupportsAlwaysReturnsFalse(): void
    {
        $testCases = [
            [null, null],
            ['resource', null],
            [null, 'type'],
            ['resource', 'type'],
            ['', ''],
            [123, 'number'],
            [[], 'array'],
        ];

        foreach ($testCases as [$resource, $type]) {
            $this->assertFalse($this->loader->supports($resource, $type));
        }
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $result = $this->loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testAutoloadContainsPayByNotificationControllerRoutes(): void
    {
        $collection = $this->loader->autoload();

        $this->assertGreaterThan(0, $collection->count());

        $routes = $collection->all();
        $foundPayByRoute = false;

        foreach ($routes as $routeName => $route) {
            if (str_contains($routeName, 'payby') || str_contains($routeName, 'notification')) {
                $foundPayByRoute = true;
                $this->assertInstanceOf(Route::class, $route);
                break;
            }
        }

        $this->assertTrue($foundPayByRoute, 'PayBy notification controller routes should be loaded');
    }

    public function testLoadWithDifferentResources(): void
    {
        $testResources = [
            'test1',
            123,
            [],
            null,
            new \stdClass(),
        ];

        foreach ($testResources as $resource) {
            $result = $this->loader->load($resource);
            $this->assertInstanceOf(RouteCollection::class, $result);
        }
    }

    public function testMultipleAutoloadCallsReturnConsistentResults(): void
    {
        $firstCall = $this->loader->autoload();
        $secondCall = $this->loader->autoload();

        $this->assertEquals($firstCall->count(), $secondCall->count());

        $firstRoutes = $firstCall->all();
        $secondRoutes = $secondCall->all();

        $this->assertEquals(array_keys($firstRoutes), array_keys($secondRoutes));

        foreach ($firstRoutes as $routeName => $route) {
            $this->assertTrue(isset($secondRoutes[$routeName]));
            $this->assertEquals($route->getPath(), $secondRoutes[$routeName]->getPath());
            $this->assertEquals($route->getMethods(), $secondRoutes[$routeName]->getMethods());
        }
    }

    public function testRouteCollectionIsNotEmpty(): void
    {
        $collection = $this->loader->autoload();

        $this->assertGreaterThan(0, $collection->count(), 'Route collection should not be empty');
    }
}
