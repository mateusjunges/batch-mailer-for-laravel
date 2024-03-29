<?php declare(strict_types=1);

namespace Junges\BatchMailer;

use Illuminate\Contracts\Foundation\Application;
use Junges\BatchMailer\Contracts\BatchMailer as BatchMailerContract;
use Junges\BatchMailer\Contracts\BatchTransport;
use Junges\BatchMailer\Contracts\Factory;
use Junges\BatchMailer\Transports\ArrayTransport;
use Junges\BatchMailer\Transports\FailoverTransport;
use Junges\BatchMailer\Transports\MailgunBatchTransport;
use Junges\BatchMailer\Transports\PostmarkBatchTransport;
use Mailgun\Mailgun;

/** @mixin \Junges\BatchMailer\Contracts\BatchMailer|\Junges\BatchMailer\BatchMailer */
final class BatchMailManager implements Factory
{
    /** @var array<string, BatchMailerContract>  */
    private array $mailers = [];

    /** @var array<string, \Closure> $customCreators */
    private array $customCreators = [];

    public function __construct(private Application $app) {}

    public function mailer(string $name = null): BatchMailerContract
    {
        $name = $name ?: $this->getDefaultDriver();

        return $this->mailers[$name] = $this->get($name);
    }

    public function get(string $name): BatchMailerContract
    {
        return $this->mailers[$name] ?? $this->resolve($name);
    }

    public function resolve(string $name): BatchMailerContract
    {
        $config = $this->getConfig($name);

        if ($config === null) {
            throw new \InvalidArgumentException("Mailer [$name] is not defined.");
        }

        return new BatchMailer(
            $name,
            $this->app['view'],
            $this->createBatchTransport($config),
            $this->app['events']
        );
    }

    protected function createBatchTransport(array $config): BatchTransport
    {
        $transport = $config['transport'] ?? $this->app['config']['batch-mailer.driver'];

        if ($this->hasCustomCreatorFor($transport)) {
            return call_user_func($this->customCreators[$transport], $config);
        }

        if (trim($transport ?? '') === '' || ! method_exists($this, $method = 'create'.ucfirst((string) $transport).'Transport')) {
            throw new \InvalidArgumentException("Unsupported batch mailer transport [$transport].");
        }

        return $this->{$method}($config);
    }

    /** @param array<string, mixed> $config */
    private function createMailgunTransport(array $config): BatchTransport
    {
        $apiClient = Mailgun::create($config['api_token']);

        return new MailgunBatchTransport(
            $apiClient,
            (string) $config['domain']
        );
    }

    /** @param array<string, mixed> $config */
    private function createPostmarkTransport(array $config): BatchTransport
    {
        return new PostmarkBatchTransport();
    }

    /** @param array<string, mixed> $config */
    private function createFailoverTransport(array $config): BatchTransport
    {
        $transports = [];

        foreach ($config['mailers'] as $name) {
            $mailerConfig = $this->getConfig($name);

            if ($mailerConfig === null) {
                throw new \InvalidArgumentException("Mailer [$name] is not defined.");
            }

            $transports[] = $this->app['config']['batch-mailer.driver']
                ? $this->createBatchTransport(array_merge($mailerConfig, ['transport' => $name]))
                : $this->createBatchTransport($mailerConfig);
        }

        return new FailoverTransport($transports);
    }

    private function createArrayTransport(): BatchTransport
    {
        return new ArrayTransport();
    }

    protected function getConfig(string $name): ?array
    {
        return $this->app['config']['batch-mailer.driver']
            ? $this->app['config']['batch-mailer']
            : $this->app['config']["batch-mailer.mailers.$name"];
    }

    /** Get the default batch mailer driver name. */
    public function getDefaultDriver(): string
    {
        return $this->app['config']['batch-mailer.driver']
            ?? $this->app['config']['batch-mailer.default'];
    }

    /** Set the default batch mailer driver name.*/
    public function setDefaultDriver(string $name): void
    {
        if ($this->app['config']['batch-mailer.driver']) {
            $this->app['config']['batch-mailer.driver'] = $name;
        }

        $this->app['config']['batch-mailer.default'] = $name;
    }

    /** Disconnect the given mailer and remove from local cache. */
    public function purge($name): void
    {
        $name = $name ?: $this->getDefaultDriver();

        unset($this->mailers[$name]);
    }

    /** Register a custom transport creator Closure. */
    public function extend(string $driver, \Closure $callback): self
    {
        $this->customCreators[$driver] = $callback;

        return $this;
    }

    /** Get the application instance used by the manager. */
    public function getApplication(): Application
    {
        return $this->app;
    }

    private function hasCustomCreatorFor(string $transport): bool
    {
        return isset($this->customCreators[$transport]);
    }

    public function __call(string $method, array $arguments)
    {
        return $this->mailer()->$method(...$arguments);
    }
}