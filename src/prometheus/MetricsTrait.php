<?php


namespace alan\msf_prometheus\prometheus;


use alan\msf_prometheus\prometheus\storage\Redis;

trait MetricsTrait
{
    public function metricsOutput()
    {
        $registry = $this->getObject(CollectorRegistry::class, [$this->getObject(Redis::class, [$this->getRedisPool('prometheus')])]);
        $renderer = new RenderTextFormat();
        return $renderer->render((yield $registry->getMetricFamilySamples()) ?: []);
    }
}