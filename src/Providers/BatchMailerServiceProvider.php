<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Providers;

use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use InteractionDesignFoundation\BatchMailer\BatchMailManager;

final class BatchMailerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/batch-mailer.php' => config_path('batch-mailer.php'),
        ], 'batch-mailer-config');
    }

    public function register(): void
    {
        $this->registerBatchMailers();
    }

    /** @return array<int, string> */
    public function provides(): array
    {
        return [
            'batch-mailer.manager',
            'batch-mailer'
        ];
    }

    private function registerBatchMailers(): void
    {
        $this->app->singleton(
            'batch-mailer.manager',
            fn (Application $app) => new BatchMailManager($app)
        );

        $this->app->singleton(
            'batch-mailer',
            fn(Application $app) => $app->make('batch-mailer.manager')->mailer()
        );
    }

    private function publishesConfiguration(): void
    {
        $this->publishes([
            __DIR__."/../../config/batch-mailer.php" => config_path('batch-mailer.php')
        ], 'batch-mailer-config');
    }
}