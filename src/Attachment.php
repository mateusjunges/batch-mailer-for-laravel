<?php

namespace InteractionDesignFoundation\BatchMailer;

use Closure;
use Illuminate\Container\Container;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Support\Traits\Macroable;

class Attachment
{
    use Macroable;

    /** The attached file's filename. */
    public ?string $as = null;

    /** The attached file's mime type. */
    public ?string $mime = null;

    /** A callback that attaches the attachment to the mail message. */
    protected \Closure $resolver;

    /** Create a mail attachment.  */
    private function __construct(Closure $resolver)
    {
        $this->resolver = $resolver;
    }

    /** Create a mail attachment from a path. */
    public static function fromPath(string $path): static
    {
        return new static(fn ($attachment, $pathStrategy) => $pathStrategy($path, $attachment));
    }

    /** Create a mail attachment from in-memory data. */
    public static function fromData(Closure $data, string $name): static
    {
        return (new static(
            fn ($attachment, $pathStrategy, $dataStrategy) => $dataStrategy($data, $attachment)
        ))->as($name);
    }

    /** Create a mail attachment from a file in the default storage disk. */
    public static function fromStorage(string $path): self
    {
        return static::fromStorageDisk(null, $path);
    }

    /** Create a mail attachment from a file in the specified storage disk. */
    public static function fromStorageDisk($disk, $path): self
    {
        return new static(function ($attachment, $pathStrategy, $dataStrategy) use ($disk, $path) {
            $storage = Container::getInstance()->make(
                FilesystemFactory::class
            )->disk($disk);

            $attachment
                ->as($attachment->as ?? basename($path))
                ->withMime($attachment->mime ?? $storage->mimeType($path));

            return $dataStrategy(fn () => $storage->get($path), $attachment);
        });
    }

    /** Set the attached file's filename. */
    public function as(string $name): self
    {
        $this->as = $name;

        return $this;
    }

    /** Set the attached file's mime type. */
    public function withMime($mime): self
    {
        $this->mime = $mime;

        return $this;
    }

    /** Attach the attachment with the given strategies. */
    public function attachWith(Closure $pathStrategy, Closure $dataStrategy): self|BatchMailerMessage|Mailable
    {
        return ($this->resolver)($this, $pathStrategy, $dataStrategy);
    }

    /** Attach the attachment to a built-in mail type. */
    public function attachTo(Mailable|BatchMailerMessage $mail): self|BatchMailerMessage|Mailable
    {
        return $this->attachWith(
            fn ($path) => $mail->attach($path, ['as' => $this->as, 'mime' => $this->mime]),
            fn ($data) => $mail->attachData($data(), $this->as, ['mime' => $this->mime])
        );
    }

    /** Determine if the given attachment is equivalent to this attachment. */
    public function isEquivalent(Attachment $attachment): bool
    {
        return $this->attachWith(
                fn ($path) => [$path, ['as' => $this->as, 'mime' => $this->mime]],
                fn ($data) => [$data(), ['as' => $this->as, 'mime' => $this->mime]],
            ) === $attachment->attachWith(
                fn ($path) => [$path, ['as' => $attachment->as, 'mime' => $attachment->mime]],
                fn ($data) => [$data(), ['as' => $attachment->as, 'mime' => $attachment->mime]],
            );
    }
}
