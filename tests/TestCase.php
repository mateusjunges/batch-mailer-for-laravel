<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use InteractionDesignFoundation\BatchMailer\Providers\BatchMailerServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app['config']->set('batch-mailer.attachments_temp_path', 'batch-mailer-temp');
    }

    public function getPackageProviders($app): array
    {
        return [BatchMailerServiceProvider::class];
    }
}