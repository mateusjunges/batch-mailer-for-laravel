<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Transports;

use InteractionDesignFoundation\BatchMailer\Attachment;
use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Enums\ClickTracking;
use InteractionDesignFoundation\BatchMailer\Exceptions\TransportException;
use InteractionDesignFoundation\BatchMailer\SentMessage;
use InteractionDesignFoundation\Postmark\Api\Message\Requests\Address;
use InteractionDesignFoundation\Postmark\Api\Message\Requests\Attachment as PostmarkAttachment;
use InteractionDesignFoundation\Postmark\Api\Message\Requests\Batch;
use InteractionDesignFoundation\Postmark\Api\Message\Requests\Message;
use InteractionDesignFoundation\Postmark\Enums\TrackLinksEnum;
use InteractionDesignFoundation\Postmark\Facades\Postmark;

final class PostmarkBatchTransport implements BatchTransport, \Stringable
{
    private const MAX_RECIPIENTS = 500;

    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
    {
        $recipientsCount = count($batchMailerMessage->recipients());

        if ($recipientsCount > self::MAX_RECIPIENTS) {
            $exception = new TransportException("Number of recipients $recipientsCount is too high to send within a single batch.");
            $exception->appendDebug($exception->getMessage());

            throw $exception;
        }

        $messages = new Batch();

        foreach ($batchMailerMessage->recipients() as $recipient) {
            $message = (new Message())
                ->setHtmlBody($batchMailerMessage->html())
                ->setTextBody($batchMailerMessage->text())
                ->setFromAddress(new Address(
                    $batchMailerMessage->from()->email,
                    $batchMailerMessage->from()->fullName,
                ))
                ->addToAddress(new Address(
                    $recipient->email,
                    $recipient->getFullName()
                ))
                ->setSubject($batchMailerMessage->subject())
                ->setMessageStream($batchMailerMessage->messageStream() ?? 'broadcast');

            foreach ($batchMailerMessage->replyTo() as $replyTo) {
                $message->addReplyTo(new Address(
                    $replyTo->email,
                    $replyTo->getFullName(),
                ));
            }

            foreach ($batchMailerMessage->cc() as $cc) {
                $message->addCc(new Address($cc->email, $cc->getFullName()));
            }

            foreach ($batchMailerMessage->bcc() as $bcc) {
                $message->addBcc(new Address($bcc->email, $bcc->getFullName()));
            }

            foreach ($batchMailerMessage->attachments() as $attachment) {
                assert($attachment instanceof Attachment);

                $message->addAttachment(PostmarkAttachment::fromFile(
                    $attachment['attachment'],
                    $attachment['options']['as'] ?? basename((string) $attachment['attachment']),
                    $attachment['options']['mime'] ?? $this->guessMimeType($attachment['attachment'])
                ));
            }

            if ($batchMailerMessage->hasTag()) {
                /** @see https://postmarkapp.com/developer/api/email-api#send-batch-emails */
                $message->addTag($batchMailerMessage->firstTag());
            }

            if ($batchMailerMessage->clickTrackingType() === ClickTracking::HTML_AND_TEXT) {
                $message->setTrackLinks(TrackLinksEnum::HTML_AND_TEXT);
            }

            if ($batchMailerMessage->clickTrackingType() === ClickTracking::HTML_ONLY) {
                $message->setTrackLinks(TrackLinksEnum::HTML_ONLY);
            }

            if ($batchMailerMessage->clickTrackingType() === ClickTracking::TEXT_ONLY) {
                $message->setTrackLinks(TrackLinksEnum::TEXT_ONLY);
            }

            if ($batchMailerMessage->clickTrackingType() === ClickTracking::NONE) {
                $message->setTrackLinks(TrackLinksEnum::NONE);
            }

            if ($batchMailerMessage->hasHeaders()) {
                $message->setHeaders($this->preparePostmarkHeaders($batchMailerMessage->headers()));
            }

            $messages->push($message);
        }

        try {
            Postmark::messages()->sendBatch($messages);
        } catch (\Throwable $throwable) {
            $exception = new TransportException($throwable->getMessage(), 0, $throwable);
            $exception->appendDebug($throwable->getMessage());

            throw $exception;
        }

        return new SentMessage($batchMailerMessage);
    }

    private function preparePostmarkHeaders(array $headers): array
    {
        $postmarkHeaders = [];

        foreach ($headers as $key => $value) {
            $postmarkHeaders[] = [
                "Name" => $key,
                "Value" => $value
            ];
        }

        return $postmarkHeaders;
    }

    private function guessMimeType(string $filePath): string
    {
        $mime = mime_content_type($filePath) ?: null;

        return $mime ?? 'application/octet-stream';
    }

    public function getNameSymbol(): string
    {
        return 'postmark';
    }

    public function __toString(): string
    {
        return $this->getNameSymbol();
    }
}