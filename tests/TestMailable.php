<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Tests;

use InteractionDesignFoundation\BatchMailer\Contracts\BatchMailable;
use InteractionDesignFoundation\BatchMailer\Mailable;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Address;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Attachment;

class TestMailable extends Mailable {
    /** @return array<int, Attachment> */
    public function attachments(): array
    {
        return [
            Attachment::fromPath(__DIR__."/test-file-attachment.txt")->as('Test File Attachment')
        ];
    }

    public function build(): BatchMailable
    {
        return $this->from(new Address('from@example.com', 'From'))
            ->replyTo('mateus@example.com', 'Mateus')
            ->html("<html>Test</html>");
    }
}