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
use alan\msf_prometheus\prometheus\exception\MetricsRegistrationException;
use alan\msf_prometheus\prometheus\storage\Redis;
use App\Exceptions\UserException;
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
        $beginTime = $this->getMillisecond();
        $httpCode = 200;
        try {
            $resp = yield $closure();
        }catch (UserException $userException) {
            $httpCode = 400;
        }catch (\Exception $exception){
            $httpCode = 500;
        } finally {
            $method = $this->getContext()->getInput()->getRequestMethod();
            $path =  $this->getContext()->getInput()->getRequestUri();
            /** @var CollectorRegistry $registry */
            $registry = $this->getObject(CollectorRegistry::class, [$this->getObject(Redis::class, [$this->getRedisPool('prometheus')])]);
//            $labels = $this->getLabels($this, $httpCode);

//        yield $registry->getOrRegisterGauge( APP_NAME, 'some_metric', 'this is for testing', ['foo', 'bar'])
//            ->set(123, ['lalal', 'lululu']);

            try {
                yield $registry->getOrRegisterCounter(
                    'http_server_requests',
                    'code_total',
                    'http server requests count.',
                    ['path', 'code','method']
                )->inc(["path" => $path, "code" => $httpCode ,"method" => $method]);

                yield $registry
                    ->getOrRegisterHistogram(
                        'http_server_requests',
                        'duration_ms',
                        'http server requests duration(ms).',
                        ['path'],
                        [25, 50, 100, 250, 500, 1000, 1500, 2000, 3000]
                    )->observe($this->getMillisecond() - $beginTime, ['path' => $path]);
            }catch (MetricsRegistrationException $metricsRegistrationException) {
                getInstance()->log->error(sprintf("MetricsRegistrationException: %s", $metricsRegistrationException->getMessage()));
            }
        }

        return yield $resp;

    }

    /**
     * 时间戳 - 精确到毫秒
     * @return float
     */
    public function getMillisecond() {
        list($t1, $t2) = explode(' ', microtime());
        return (float)sprintf('%.0f',(floatval($t1)+floatval($t2))*1000);
    }
}