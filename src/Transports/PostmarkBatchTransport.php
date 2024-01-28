<?php declare(strict_types=1);

namespace Junges\BatchMailer\Transports;

use Junges\BatchMailer\BatchMailerMessage;
use Junges\BatchMailer\Contracts\BatchTransport;
use Junges\BatchMailer\Enums\ClickTracking;
use Junges\BatchMailer\Exceptions\TransportException;
use Junges\BatchMailer\Mailables\Attachment;
use Junges\BatchMailer\SentMessage;
use Junges\Postmark\Api\Message\Requests\Address;
use Junges\Postmark\Api\Message\Requests\Attachment as PostmarkAttachment;
use Junges\Postmark\Api\Message\Requests\Batch;
use Junges\Postmark\Api\Message\Requests\Message;
use Junges\Postmark\Enums\TrackLinksEnum;
use Junges\Postmark\Facades\Postmark;

final class PostmarkBatchTransport implements BatchTransport
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
                ->when($batchMailerMessage->html(), fn (Message $message) => $message->setHtmlBody($batchMailerMessage->html()))
                ->when($batchMailerMessage->text(), fn (Message $message) => $message->setTextBody($batchMailerMessage->text()))
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

            foreach ($batchMailerMessage->getPreparedAttachments() as $attachment) {
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

            if ($batchMailerMessage->shouldTrackOpenings()) {
                $message->setOpenTracking(true);
            } else {
                $message->setOpenTracking(false);
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