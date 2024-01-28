<?php declare(strict_types=1);

namespace Junges\BatchMailer\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Symfony\Component\Console\Input\InputOption;

final class BatchMailMakeCommand extends GeneratorCommand
{
    /** @var string $name */
    protected $name = 'make:batch-mail';

    /** @var string $defaultName */
    protected static $defaultName = 'make:batch-mail';

    /** @var string $description */
    protected $description = 'Create a new batch email class';

    /** @var string $type */
    protected $type = 'BatchMail';

    protected function getStub(): string
    {
        return $this->resolveStubPath('/stubs/batch-mail.stub');
    }

    private function resolveStubPath(string $stub): string
    {
        return file_exists($customPath = $this->laravel->basePath(trim($stub, '/')))
            ? $customPath
            : __DIR__.$stub;
    }

    /**
     * Get the console command options.
     *
     * @return array<int, array<int, string>
     */
    protected function getOptions(): array
    {
        return [
            ['force', 'f', InputOption::VALUE_NONE, 'Create the class even if the mailable already exists']
        ];
    }

    protected function getDefaultNamespace($rootNamespace): string
    {
        return "$rootNamespace\Mail\BatchMail";
    }
}