<?php
declare(strict_types = 1);

namespace alan\msf_prometheus\prometheus\storage;


use alan\msf_prometheus\prometheus\MetricFamilySamples;

interface Adapter
{
    const COMMAND_INCREMENT_INTEGER = 1;
    const COMMAND_INCREMENT_FLOAT = 2;
    const COMMAND_SET = 3;

    /**
     * @return MetricFamilySamples[]
     */
    public function collect();

    /**
     * @param array $data
     * @return void
     */
    public function updateHistogram(array $data);

    /**
     * @param array $data
     * @return void
     */
    public function updateGauge(array $data);

    /**
     * @param array $data
     * @return void
     */
    public function updateCounter(array $data);
}
