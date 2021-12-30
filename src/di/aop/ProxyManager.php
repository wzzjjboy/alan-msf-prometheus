<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace alan\msf_prometheus\di\aop;

use alan\msf_prometheus\utils\filesystem\filesystem;
use Roave\BetterReflection\Reflection\ReflectionClass;

class ProxyManager
{
    /**
     * The map to collect the classes whith paths.
     *
     * @var array
     */
    protected $classMap = [];

    /**
     * The classes which be rewrited by proxy.
     *
     * @var array
     */
    protected $proxies = [];

    /**
     * The directory which the proxy file places in.
     *
     * @var string
     */
    protected $proxyDir;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    public function __construct(
        array $reflectionClassMap = [],
        array $composerLoaderClassMap = [],
        string $proxyDir = ''
    ) {
        $this->classMap = $this->mergeClassMap($reflectionClassMap, $composerLoaderClassMap);
        $this->proxyDir = $proxyDir;
        $this->filesystem = new Filesystem();
        $this->proxies = $this->generateProxyFiles($this->initProxiesByReflectionClassMap(
            $this->classMap
        ));
    }

    public function getProxies(): array
    {
        return $this->proxies;
    }

    public function getProxyDir(): string
    {
        return $this->proxyDir;
    }

    /**
     * @param ReflectionClass[] $reflectionClassMap
     */
    protected function mergeClassMap(array $reflectionClassMap, array $composerLoaderClassMap): array
    {
        $classMap = [];
        foreach ($reflectionClassMap as $class) {
            $classMap[$class->getName()] = $class->getFileName();
        }

        return array_merge($classMap, $composerLoaderClassMap);
    }

    protected function generateProxyFiles(array $proxies = []): array
    {
        $proxyFiles = [];
        if (! $proxies) {
            return $proxyFiles;
        }
        if (! file_exists($this->getProxyDir())) {
            mkdir($this->getProxyDir(), 0755, true);
        }
        // WARNING: Ast class SHOULD NOT use static instance, because it will read  the code from file, then would be caused coroutine switch.

        AstVisitorRegistry::insert(ProxyCallVisitor::class, PHP_INT_MAX / 2);
        $ast = new Ast();
//        var_dump($proxies); die;
        foreach ($proxies as $className => $aspects) {
            $proxyFiles[$className] = $this->putProxyFile($ast, $className);
        }
        return $proxyFiles;
    }

    protected function putProxyFile(Ast $ast, $className)
    {
        $proxyFilePath = $this->getProxyFilePath($className);
        $modified = true;
        if (file_exists($proxyFilePath)) {
            $modified = $this->isModified($className, $proxyFilePath);
        }

        if ($modified) {
            $code = $ast->proxy($className);
            file_put_contents($proxyFilePath, $code);
        }

        return $proxyFilePath;
    }

    protected function isModified(string $className, string $proxyFilePath = null): bool
    {
        $proxyFilePath = $proxyFilePath ?? $this->getProxyFilePath($className);
        $time = $this->filesystem->lastModified($proxyFilePath);
        $origin = $this->classMap[$className];
        if ($time >= $this->filesystem->lastModified($origin)) {
            return false;
        }

        return true;
    }

    protected function getProxyFilePath($className)
    {
        return $this->getProxyDir() . str_replace('\\', '_', $className) . '.proxy.php';
    }

    protected function initProxiesByReflectionClassMap(array $reflectionClassMap = []): array
    {
        // According to the data of AspectCollector to parse all the classes that need proxy.
        $proxies = [];
        if (! $reflectionClassMap) {
            return $proxies;
        }
        foreach ($reflectionClassMap as $className => $file) {
            if ($this->isMatch($className)) {
                $proxies[$className] = $file;
            }
        }
        return $proxies;
    }

    protected function isMatch(string $target): bool
    {
        if (stripos(trim($target, '\\'), 'App\Controllers') === 0){
            return true;
        }

        return false;
    }
}
