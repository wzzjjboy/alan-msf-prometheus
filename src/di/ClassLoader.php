<?php


namespace alan\msf_prometheus\di;

use alan\msf_prometheus\di\aop\ProxyManager;
use alan\msf_prometheus\utils\Composer;
use Composer\Autoload\ClassLoader as ComposerClassLoader;
use alan\msf_prometheus\di\Scanner;


class ClassLoader
{
    /**
     * @var \Composer\Autoload\ClassLoader
     */
    protected $composerClassLoader;

    /**
     * The container to collect all the classes that would be proxy.
     * [ OriginalClassName => ProxyFileAbsolutePath ].
     *
     * @var array
     */
    protected $proxies = [];

    public function __construct(ComposerClassLoader $classLoader, string $proxyFileDir)
    {
//
        $this->setComposerClassLoader($classLoader);

        // Scan by ScanConfig to generate the reflection class map
        $scanner = new Scanner($this);
        $reflectionClassMap = $scanner->scan([ROOT_PATH . '/app/Controllers/']);
        $composerLoaderClassMap = $this->getComposerClassLoader()->getClassMap();
        $proxyManager = new ProxyManager($reflectionClassMap, $composerLoaderClassMap, $proxyFileDir);
        $this->proxies = $proxyManager->getProxies();
    }

    public function setComposerClassLoader(ComposerClassLoader $classLoader): self
    {
        $this->composerClassLoader = $classLoader;
        // Set the ClassLoader to alan\msf_prometheus\utils\Composer to avoid unnecessary find process.
        Composer::setLoader($classLoader);
        return $this;
    }

    public function loadClass(string $class): void
    {
        $path = $this->locateFile($class);

        if ($path) {
            include $path;
        }
    }

    protected function locateFile(string $className): ?string
    {
        if (isset($this->proxies[$className]) && file_exists($this->proxies[$className])) {
            $file = $this->proxies[$className];
        } else {
            $file = $this->getComposerClassLoader()->findFile($className);
        }

        return is_string($file) ? $file : null;
    }

    public function getComposerClassLoader(): ComposerClassLoader
    {
        return $this->composerClassLoader;
    }

    public static function init(?string $proxyFileDirPath = null): void
    {
        if (! $proxyFileDirPath) {
            // This dir is the default proxy file dir path of Hyperf
            $proxyFileDirPath = ROOT_PATH . '/runtime/proxy/';
        }

        $loaders = spl_autoload_functions();

        // Proxy the composer class loader
        foreach ($loaders as &$loader) {
            $unregisterLoader = $loader;
            if (is_array($loader) && $loader[0] instanceof ComposerClassLoader) {
                /** @var ComposerClassLoader $composerClassLoader */
                $composerClassLoader = $loader[0];
                $loader[0] = new static($composerClassLoader, $proxyFileDirPath);
            }
            spl_autoload_unregister($unregisterLoader);
        }

        unset($loader);

        // Re-register the loaders
        foreach ($loaders as $loader) {
            spl_autoload_register($loader);
        }
    }
}