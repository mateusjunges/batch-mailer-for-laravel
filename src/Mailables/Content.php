<?php

namespace Junges\BatchMailer\Mailables;

class Content
{
    public function __construct(
        public ?string $view = null,
        public ?string $html = null,
        public ?string $text = null,
        public ?string $markdown = null,
        public array $with = [],
        public ?string $htmlString = null
    ) {}

    /** Set the view for the message. */
    public function view(string $view): self
    {
        $this->view = $view;
        return $this;
    }

    /** Set the view for the message. */
    public function html(string $html): self
    {
        return $this->view($html);
    }

    /** Set the plain text view for the message. */
    public function text(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    /** Set the Markdown view for the message. */
    public function markdown(string $markdown): self
    {
        $this->markdown = $markdown;
        return $this;
    }

    /** Set the pre-rendered HTML for the message. */
    public function htmlString(string $html): self
    {
        $this->htmlString = $html;
        return $this;
    }

    /** Add a piece of data to the message. */
    public function with(string|array $key, mixed $value = null): self
    {
        if (is_array($key)) {
            $this->with = array_merge($this->with, $key);
        } else {
            $this->with[$key] = $value;
        }
        return $this;
    }
}