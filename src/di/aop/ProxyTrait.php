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

use alan\msf_prometheus\prometheus\CollectorRegistry;
use alan\msf_prometheus\prometheus\storage\Redis;
use Closure;
use PG\MSF\Controllers\Controller;

trait ProxyTrait
{
    protected function __proxyCall(
        string $className,
        string $method,
        array $arguments,
        Closure $closure
    ) {
        $beginTime = microtime(true);
        $resp = yield $closure();
        $httpCode = 200;
        /** @var CollectorRegistry $registry */
        $registry = $this->getObject(CollectorRegistry::class, [$this->getObject(Redis::class, [$this->getRedisPool('prometheus')])]);
        $labels = $this->getLabels($this, $httpCode);
        yield $registry
            ->getOrRegisterHistogram(  APP_NAME, 'http_requests', 'http requests histogram!', array_keys($labels))
            ->observe(microtime(true) - $beginTime, $labels);

//        yield $registry->getOrRegisterGauge( APP_NAME, 'some_metric', 'this is for testing', ['foo', 'bar'])
//            ->set(123, ['lalal', 'lululu']);

        yield $registry->getOrRegisterCounter(APP_NAME, 'http_requests_totoal', 'http requests counter', ['requests_uri', 'request_method'])
            ->inc([$this->getContext()->getInput()->getRequestUri(), $this->getContext()->getInput()->getRequestMethod()]);

        return yield $resp;
    }

    /**
     * @param Controller $instance
     * @param $httpCode
     * @return array
     */
    private function getLabels($instance, $httpCode)
    {
        return [
            'request_status' => $httpCode,
            'request_type'   => $instance->getRequestType(),
            'request_path'   => $instance->getContext()->getInput()->getRequestUri(),
            'request_method' => $instance->getContext()->getInput()->getRequestMethod(),
            'hostname'       => gethostname(),
            'instance'       => sprintf("%s:9000", gethostname()),
            'ip'             => current(swoole_get_local_ip()),
        ];
    }
}
