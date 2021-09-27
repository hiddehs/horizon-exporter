<?php

namespace RenokiCo\HorizonExporter\Metrics;

use Laravel\Horizon\Contracts\MetricsRepository;
use RenokiCo\LaravelExporter\Metric;

class JobsRuntime extends Metric
{
    /**
     * The collector to store the metric.
     *
     * @var \Prometheus\Gauge
     */
    protected $collector;

    /**
     * Perform the update call on the collector.
     *
     * @return void
     */
    public function update(): void
    {
        if ($jobs = app(MetricsRepository::class)->measuredJobs()) {
            foreach ($jobs as $job) {
                $value = collect(config('horizon-exporter.include_snapshots') ? app(MetricsRepository::class)->snapshotsForJob($job) : [])
                    ->merge([(object) ['runtime' => app(MetricsRepository::class)->runtimeForJob($job)]])
                    ->average('runtime');

                $this->collector->set(
                    value: $value,
                    labels: [
                        'job' => $job,
                    ],
                );
            }
        }
    }

    /**
     * Register the collector to the registry.
     *
     * @return \Prometheus\Collector
     */
    public function registerCollector()
    {
        return $this->collector = $this->registry->registerGauge(
            namespace: $this->getNamespace(),
            name: 'horizon_job_runtime',
            help: 'Get total workload runtime by job name.',
            labels: ['job'],
        );
    }
}
