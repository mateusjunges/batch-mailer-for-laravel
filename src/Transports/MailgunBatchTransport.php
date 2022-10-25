<?php declare(strict_types=1);

namespace InteractionDesignFoundation\BatchMailer\Transports;

use InteractionDesignFoundation\BatchMailer\BatchMailerMessage;
use InteractionDesignFoundation\BatchMailer\Contracts\BatchTransport;
use InteractionDesignFoundation\BatchMailer\ValueObjects\Attachment;
use InteractionDesignFoundation\BatchMailer\Enums\ClickTracking;
use InteractionDesignFoundation\BatchMailer\Exceptions\MissingRequiredParameter;
use InteractionDesignFoundation\BatchMailer\Exceptions\TooManyRecipients;
use InteractionDesignFoundation\BatchMailer\SentMessage;
use Mailgun\Mailgun;

final class MailgunBatchTransport implements BatchTransport
{
    private const MAX_RECIPIENTS = 500;

    public function __construct(private readonly Mailgun $apiClient, private readonly string $domain)
    {}

    /**
     * @throws \InteractionDesignFoundation\BatchMailer\Exceptions\TooManyRecipients
     * @throws \InteractionDesignFoundation\BatchMailer\Exceptions\MissingRequiredParameter
     * @throws \Mailgun\Message\Exceptions\LimitExceeded
     */
    public function send(BatchMailerMessage $batchMailerMessage): ?SentMessage
    {
        $recipientsCount = count($batchMailerMessage->recipients());

        if ($recipientsCount > self::MAX_RECIPIENTS) {
            throw new TooManyRecipients("Number of recipients $recipientsCount is too high to send within a single batch.");
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
                $message->addTag(implode(', ', $batchMailerMessage->tags()));
            }

            if ($batchMailerMessage->shouldTrackOpenings()) {
                $message->setOpenTracking(true);
            }

            if ($batchMailerMessage->shouldNotTrackOpenings()) {
                $message->setOpenTracking(false);
            }

            if ($batchMailerMessage->hasReplyToAddresses()) {
                $message->setReplyToAddress($batchMailerMessage->replyTo()->email, ['full_name' => $batchMailerMessage->replyTo()->getFullName()]);
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

                $message->addAttachment($attachment->filePath, $attachment->name);
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
            throw new TooManyRecipients($tooManyRecipients->getMessage(), 0, $tooManyRecipients);
        }

        try {
            $message->finalize();
        } catch (\Mailgun\Message\Exceptions\MissingRequiredParameter $missingRequiredParameter) {
            throw new MissingRequiredParameter($missingRequiredParameter->getMessage(), 0, $missingRequiredParameter);
        }

        return new SentMessage($batchMailerMessage);
    }

    public function getNameSymbol(): string
    {
        return 'mailgun';
    }
}