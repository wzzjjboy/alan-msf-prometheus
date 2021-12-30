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
namespace alan\msf_prometheus\di;

use alan\msf_prometheus\Config\ProviderConfig;
use alan\msf_prometheus\di\BetterReflectionManager;
use alan\msf_prometheus\di\ClassLoader;
use alan\msf_prometheus\di\exception\DirectoryNotExistException;
use alan\msf_prometheus\di\MetadataCollector;
use alan\msf_prometheus\utils\filesystem\Filesystem;
use ReflectionProperty;
use Roave\BetterReflection\Reflection\Adapter;
use Roave\BetterReflection\Reflection\ReflectionClass;

class Scanner
{
    /**
     * @var \alan\msf_prometheus\di\ClassLoader
     */
    protected $classloader;

    /**
     * @var ScanConfig
     */
    protected $scanConfig;

    /**
     * @var Filesystem
     */
    protected $filesystem;

    /**
     * @var string
     */
    protected $path = ROOT_PATH . '/runtime/container/collectors.cache';

    public function __construct(ClassLoader $classloader)
    {
        $this->classloader = $classloader;
        $this->filesystem = new Filesystem();
    }

    /**
     * @return ReflectionClass[]
     */
    public function scan($paths): array
    {
        $paths = $this->normalizeDir($paths);

        $reflector = BetterReflectionManager::initClassReflector($paths);
        $classes = $reflector->getAllClasses();
        // Initialize cache for BetterReflectionManager.
        foreach ($classes as $class) {
            BetterReflectionManager::reflectClass($class->getName(), $class);
        }
        return $classes;
    }

    /**
     * Normalizes given directory names by removing directory not exist.
     * @throws DirectoryNotExistException
     */
    public function normalizeDir(array $paths): array
    {
        $result = [];
        foreach ($paths as $path) {
            if (is_dir($path)) {
                $result[] = $path;
            }
        }

        if ($paths && ! $result) {
            throw new DirectoryNotExistException('The scanned directory does not exist');
        }

        return $result;
    }

    protected function deserializeCachedCollectors(array $collectors): int
    {
        if (! file_exists($this->path)) {
            return 0;
        }

        $data = unserialize(file_get_contents($this->path));
        foreach ($data as $collector => $deserialized) {
            /** @var MetadataCollector $collector */
            if (in_array($collector, $collectors)) {
                $collector::deserialize($deserialized);
            }
        }

        return $this->filesystem->lastModified($this->path);
    }

    /**
     * @param ReflectionClass[] $reflections
     */
    protected function clearRemovedClasses(array $collectors, array $reflections): void
    {
        $path = ROOT_PATH . '/runtime/container/classes.cache';
        $classes = [];
        foreach ($reflections as $reflection) {
            $classes[] = $reflection->getName();
        }

        $data = [];
        if ($this->filesystem->exists($path)) {
            $data = unserialize($this->filesystem->get($path));
        }

        $this->putCache($path, serialize($classes));

        $removed = array_diff($data, $classes);

        foreach ($removed as $class) {
            /** @var MetadataCollector $collector */
            foreach ($collectors as $collector) {
                $collector::clear($class);
            }
        }
    }

    protected function putCache($path, $data)
    {
        if (! $this->filesystem->isDirectory($dir = dirname($path))) {
            $this->filesystem->makeDirectory($dir, 0755, true);
        }

        $this->filesystem->put($path, $data);
    }
}
