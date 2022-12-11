<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Transports;

use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\Enums\ClickTracking;
use InteractionDesignFoundation\BatchMailer\Exceptions\TransportException;
use InteractionDesignFoundation\BatchMailer\Mailable\Attachment;
use InteractionDesignFoundation\BatchMailer\SentMessage;
use Mailgun\Mailgun;
use Mailgun\Message\Exceptions\LimitExceeded;

final class MailgunBatchTransport implements BatchTransport
{
    private const MAX_RECIPIENTS = 500;

    public function __construct(private readonly Mailgun $apiClient, private readonly string $domain)
    {}

    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
    {
        $recipientsCount = count($batchMailerMessage->recipients());

        if ($recipientsCount > self::MAX_RECIPIENTS) {
            throw new TransportException("Number of recipients $recipientsCount is too high to send within a single batch.");
        }

        $message = $this->apiClient->messages()->getBatchMessage($this->domain);

        try {
            foreach ($batchMailerMessage->recipients() as $recipient) {
                $message->addToRecipient($recipient->email, ['full_name' => $recipient->getFullName()]);
            }

            $message->setFromAddress($batchMailerMessage->from()->email, ['full_name' => $batchMailerMessage->from()->fullName]);
            $message->setHtmlBody($batchMailerMessage->html());

            if ($batchMailerMessage->hasText()) {
                $message->setTextBody($batchMailerMessage->text());
            }

            if ($batchMailerMessage->hasTag()) {
                /** @see https://documentation.mailgun.com/en/latest/user_manual.html?highlight=X-Mailgun-Tag#sending-via-smtp */
                foreach ($batchMailerMessage->tags() as $tag) {
                    $message->addTag($tag);
                }
            }

            if ($batchMailerMessage->shouldTrackOpenings()) {
                $message->setOpenTracking(true);
            }

            if ($batchMailerMessage->shouldNotTrackOpenings()) {
                $message->setOpenTracking(false);
            }

            if ($batchMailerMessage->hasReplyToAddresses()) {
                /**
                 * Mailgun supports only one reply to address.
                 *
                 * @see https://help.mailgun.com/hc/en-us/articles/4401814149147-Adding-A-Reply-To-Address
                 */
                $message->setReplyToAddress($batchMailerMessage->replyTo()[0]->email, ['full_name' => $batchMailerMessage->replyTo()[0]->getFullName()]);
            }

            foreach ($batchMailerMessage->cc() as $cc) {
                $message->addCcRecipient($cc->email, ['full_name' => $cc->getFullName()]);
            }

            foreach ($batchMailerMessage->bcc() as $bcc) {
                $message->addBccRecipient($bcc->email, ['full_name' => $bcc->getFullName()]);
            }

            foreach ($batchMailerMessage->headers() as $headerName => $headerData) {
                $message->addCustomHeader($headerName, $headerData);
            }

            foreach ($batchMailerMessage->attachments() as $attachment) {
                assert($attachment instanceof Attachment);

                $message->addAttachment($attachment['attachment'], $attachment['options']['as'] ?? null);
            }

            foreach ($batchMailerMessage->campaignIds() as $campaignId) {
                $message->addCampaignId($campaignId);
            }

            $shouldTrackLinkClicks = false;
            $trackHtmlLinksOnly = false;

            if ($batchMailerMessage->clickTrackingType() !== ClickTracking::NONE) {
                $shouldTrackLinkClicks = true;
            }
            if ($batchMailerMessage->clickTrackingType() === ClickTracking::HTML_ONLY) {
                $trackHtmlLinksOnly = true;
            }

            $message->setClickTracking($shouldTrackLinkClicks, $trackHtmlLinksOnly);

            if ($batchMailerMessage->hasDeliveryTime()) {
                $message->setDeliveryTime(
                    $batchMailerMessage->deliveryTime()->toDateString(),
                    $batchMailerMessage->deliveryTime()->getTimezone()->getName(),
                );
            }

        } catch (\Mailgun\Message\Exceptions\TooManyRecipients $tooManyRecipients) {
            $exception = new TransportException($tooManyRecipients->getMessage(), 0, $tooManyRecipients);
            $exception->appendDebug($tooManyRecipients->getMessage());

            throw $exception;
        } catch (LimitExceeded $limitExceeded) {
            $exception = new TransportException($limitExceeded->getMessage(), 0, $limitExceeded);
            $exception->appendDebug($limitExceeded->getMessage());

            throw $exception;
        } catch (\Throwable $throwable) {
            $exception = new TransportException($throwable->getMessage(), 0, $throwable);
            $exception->appendDebug($throwable->getMessage());

            throw $exception;
        }

        try {
            $message->finalize();
        } catch (\Throwable $exception) {
            $transportException =  new TransportException($exception->getMessage(), 0, $exception);
            $transportException->appendDebug($exception->getMessage());

            throw $transportException;
        }

        return new SentMessage($batchMailerMessage);
    }

    public function getNameSymbol(): string
    {
        return 'mailgun';
    }

    public function __toString(): string
    {
        return $this->getNameSymbol();
    }
}