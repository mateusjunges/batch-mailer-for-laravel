<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Transports;

use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Attachment;
use InteractionDesignFoundation\BatchMailer\Enums\ClickTracking;
use InteractionDesignFoundation\BatchMailer\Exceptions\TooManyRecipients;
use InteractionDesignFoundation\BatchMailer\SentMessage;
use Ixdf\Postmark\Api\Message\Requests\Address;
use Ixdf\Postmark\Api\Message\Requests\Attachment as PostmarkAttachment;
use Ixdf\Postmark\Api\Message\Requests\Batch;
use Ixdf\Postmark\Api\Message\Requests\Message;
use Ixdf\Postmark\Enums\TrackLinksEnum;
use Ixdf\Postmark\Facades\Postmark;

final class PostmarkBatchTransport implements BatchTransport
{
    private const MAX_RECIPIENTS = 500;

    /**
     * @throws \InteractionDesignFoundation\BatchMailer\Exceptions\TooManyRecipients
     */
    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
    {
        $recipientsCount = count($batchMailerMessage->recipients());

        if ($recipientsCount > self::MAX_RECIPIENTS) {
            throw new TooManyRecipients("Number of recipients $recipientsCount is too high to send within a single batch.");
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
                    $attachment->filePath,
                    $attachment->name,
                    $this->guessMimeType($attachment)
                ));
            }

            if ($batchMailerMessage->hasTag()) {
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

        Postmark::messages()->sendBatch($messages);

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

    private function guessMimeType(Attachment $attachment): string
    {
        $mime = mime_content_type($attachment->filePath) ? mime_content_type($attachment->filePath) : null;

        return $attachment->mimeType
            ?? $mime
            ?? 'application/octet-stream';
    }

    public function getNameSymbol(): string
    {
        return 'postmark';
    }
}