<?php

namespace App;

use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\Config\Resource\FileResource;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\RouteCollectionBuilder;

class VirtualKernel extends BaseKernel
{
    use MicroKernelTrait;

    private const CONFIG_EXTS = '.{php,xml,yaml,yml}';

    /**
     * @var string|null
     */
    protected $applicationName;

    public function __construct(string $environment, bool $debug, ?string $applicationName)
    {
        $this->applicationName = $applicationName;

        parent::__construct($environment, $debug);
    }

    public function registerBundles(): iterable
    {
        $commonBundles = require $this->getProjectDir().'/config/bundles.php';
        // Register application specific bundles if needed
        $kernelBundles = $this->applicationName
            ? require $this->getApplicationSpecificConfigPath().'/bundles.php'
            : [];

        foreach (array_merge($commonBundles, $kernelBundles) as $class => $envs) {
            if ($envs[$this->getEnvironment()] ?? $envs['all'] ?? false) {
                yield new $class();
            }
        }
    }

    public function getCacheDir(): string
    {
        // We have to override this to build a cache for each application
        $applicationName = $this->applicationName ?? 'common';
        return "{$this->getProjectDir()}/var/cache/{$applicationName}/{$this->getEnvironment()}";
    }

    public function getLogDir(): string
    {
        // We have to override this to build an application specific log
        $applicationName = $this->applicationName ?? 'common';
        return $this->getProjectDir().'/var/log/'.$applicationName;
    }

    protected function configureContainer(ContainerBuilder $container, LoaderInterface $loader): void
    {
        $container->addResource(new FileResource($this->getProjectDir().'/config/bundles.php'));
        if ($this->applicationName) {
            $container->addResource(new FileResource("{$this->getApplicationSpecificConfigPath()}/bundles.php"));
        }

        $container->setParameter('container.dumper.inline_class_loader', \PHP_VERSION_ID < 70400 || $this->debug);
        $container->setParameter('container.dumper.inline_factories', true);

        // First configure common package and services
        $this->doConfigurePackageAndServices($loader);
        // Then configure application specific package and services
        if ($this->applicationName) {
            $this->doConfigurePackageAndServices($loader, $this->applicationName);
        }
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        $this->doConfigureRoutes($routes);
        if ($this->applicationName) {
            $this->doConfigureRoutes($routes, $this->applicationName);
        }
    }

    private function getApplicationSpecificConfigPath(): string
    {
        return "{$this->getProjectDir()}/config/{$this->applicationName}";
    }

    private function doConfigurePackageAndServices(
        LoaderInterface $loader,
        string $applicationName = null
    ): void {
        $confDir = $applicationName ? $this->getApplicationSpecificConfigPath() : "{$this->getProjectDir()}/config";

        $loader->load($confDir.'/{packages}/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{packages}/'.$this->getEnvironment().'/*'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}'.self::CONFIG_EXTS, 'glob');
        $loader->load($confDir.'/{services}_'.$this->getEnvironment().self::CONFIG_EXTS, 'glob');
    }

    private function doConfigureRoutes(RouteCollectionBuilder $routes, string $applicationName = null): void
    {
        $confDir = $applicationName ? $this->getApplicationSpecificConfigPath() : "{$this->getProjectDir()}/config";

        $routes->import($confDir.'/{routes}/'.$this->getEnvironment().'/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}/*'.self::CONFIG_EXTS, '/', 'glob');
        $routes->import($confDir.'/{routes}'.self::CONFIG_EXTS, '/', 'glob');
    }
}
