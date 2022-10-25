<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer;

use Illuminate\Contracts\Foundation\Application;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailer as BatchMailerContract;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Contracts\Factory;
use InteractionDesignFoundation\BatchMailer\Transports\ArrayTransport;
use InteractionDesignFoundation\BatchMailer\Transports\FailoverTransport;
use InteractionDesignFoundation\BatchMailer\Transports\MailgunBatchTransport;
use InteractionDesignFoundation\BatchMailer\Transports\PostmarkBatchTransport;
use Mailgun\Mailgun;

final class BatchMailManager implements Factory
{
    /** @var array<string, BatchMailerContract>  */
    private array $mailers = [];

    public function __construct(private readonly Application $app) {}

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

        if (trim($transport ?? '') === '' || ! method_exists($this, $method = 'create'.ucfirst($transport).'Transport')) {
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
            $config = $this->getConfig($name);

            if ($config === null) {
                throw new \InvalidArgumentException("Mailer [$name] is not defined.");
            }

            $transports[] = $this->app['config']['batch-mailer.driver']
                ? $this->createBatchTransport(array_merge($config, ['transport' => $name]))
                : $this->createBatchTransport($config);
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

    public function getDefaultDriver(): string
    {
        return $this->app['config']['batch-mailer.driver']
            ?? $this->app['config']['batch-mailer.default'];
    }

    public function __call(string $method, array $arguments)
    {
        return $this->mailer()->$method(...$arguments);
    }
}