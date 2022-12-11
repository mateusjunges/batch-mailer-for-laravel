<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Mailable;

use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Mailable;

final class Attachment
{
    public string $as;
    public string $mimeType;

    public function __construct(
       protected \Closure $resolver
    ) {}

    /** Create a mail attachment from a path. */
    public static function fromPath(string $path): self
    {
        return new self(fn (Attachment $attachment, \Closure $pathStrategy) => $pathStrategy($path, $attachment));
    }

    /** Create a mail attachment from in-memory data. */
    public static function fromData(\Closure $data, string $name): self
    {
        return (new self(
            fn (Attachment $attachment, \Closure $pathStrategy, \Closure $dataStrategy) => $dataStrategy($data, $attachment)
        ))->as($name);
    }

    /** Create a mail attachment from a file in the specified storage disk. */
    public static function fromStorage(string $path): self
    {
        return self::fromStorageDisk(null, $path);
    }

    /** Create a mail attachment from a file in the default storage disk. */
    public static function fromStorageDisk(string|null $disk, string $path): self
    {
        return new self(function (Attachment $attachment, \Closure $pathStrategy, \Closure $dataStrategy) use ($disk, $path) {
            $storage = Container::getInstance()->make(
                Factory::class
            )->disk($disk);

            $attachment
                ->as($attachment->as ?? basename($path))
                ->withMime($attachment->mimeType ?? $storage->mimeType($path));

            return $dataStrategy(fn () => $storage->get($path), $attachment);
        });
    }

    public function as(string $name): self
    {
        $this->as = $name;

        return $this;
    }

    public function withMime(string $mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }


    public function attachTo(BatchMailerMessage|Mailable $mail): Mailable|BatchMailerMessage
    {
        return $this->attachWith(
            fn ($path) => $mail->attach($path, ['as' => $this->as ?? null, 'mime' => $this->mimeType ?? null]),
            fn ($data) => $mail->attachData($data(), $this->as, ['mime' => $this->mimeType]),
        );
    }

    public function attachWith(\Closure $pathStrategy, \Closure $dataStrategy): mixed
    {
        return ($this->resolver)($this, $pathStrategy, $dataStrategy);
    }
}