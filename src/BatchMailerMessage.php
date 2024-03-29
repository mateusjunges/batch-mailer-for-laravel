<?php declare(strict_types=1);

namespace Junges\BatchMailer;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Mail\Attachable;
use Illuminate\Support\Facades\Storage;
use Junges\BatchMailer\Enums\ClickTracking;
use Junges\BatchMailer\Mailables\Address;
use Junges\BatchMailer\Mailables\Attachment;
use Symfony\Component\Mime\Part\DataPart;

final class BatchMailerMessage
{
    /** @var array<int, \Junges\BatchMailer\Mailables\Address> $to*/
    private array $to = [];

    /** @var array<int, \Junges\BatchMailer\Mailables\Address> $to*/
    private array $replyTo = [];

    /** @var array<int, \Junges\BatchMailer\Mailables\Address> $cc*/
    private array $cc = [];

    /** @var array<int, \Junges\BatchMailer\Mailables\Address> $bcc*/
    private array $bcc = [];

    /** @var array<int, \Illuminate\Mail\Attachment> $attachments */
    private array $attachments = [];

    /** @var array<array-key, DataPart> $rawAttachments  */
    private array $rawAttachments = [];

    /** @var array<int, string> $campaignIds */
    private array $campaignIds = [];

    private string $html;

    /** @var array<string, mixed> $headers */
    private array $headers = [];

    /** @var array<string, int|float|string> $metadata */
    private array $metadata = [];

    /** @var array<int, string> $tags */
    private array $tags = [];
    private bool $shouldTrackOpenings = true;
    private ClickTracking $clickTrackingType = ClickTracking::HTML_AND_TEXT;
    private ?CarbonInterface $deliveryTime = null;
    private ?string $messageStream = null;
    private ?string $text = null;
    private string $subject;
    private Address $from;

    /** @param array<int, \Junges\BatchMailer\Mailables\Address> $to */
    public function setTo(array $to): self
    {
        $this->to = $to;

        return $this;
    }

    public function addTo(Address $recipient): self
    {
        $this->to[] = $recipient;

        return $this;
    }

    public function addReplyTo(Address $replyTo): self
    {
        $this->replyTo[] = $replyTo;

        return $this;
    }

    public function setCc(array $cc): self
    {
        $this->cc = $cc;

        return $this;
    }

    public function addCc(Address $recipient): self
    {
        $this->cc[] = $recipient;

        return $this;
    }

    public function setBcc(array $bcc): self
    {
        $this->bcc = $bcc;

        return $this;
    }

    public function addBcc(Address $recipient): self
    {
        $this->bcc[] = $recipient;

        return $this;
    }

    public function attach(Attachment|Attachable|string $attachment, array $options = []): self
    {
        if ($attachment instanceof Attachable) {
            $attachment = $attachment->toMailAttachment();
        }

        if ($attachment instanceof Attachment) {
            return $attachment->attachTo($this);
        }

        $this->attachments[] = compact('attachment', 'options');

        return $this;
    }

    public function attachData(string $data, string $name, array $options = []): self
    {
        $this->rawAttachments[] = new DataPart($data, $name, $options['mime'] ?? null);

        return $this;
    }

    public function addCampaignId(string $campaignId): self
    {
        $this->campaignIds[] = $campaignId;

        return $this;
    }

    public function setHtml(string $html): self
    {
        $this->html = $html;

        return $this;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function addCustomHeader(string $headerName, mixed $headerData): self
    {
        $this->headers[$headerName] = $headerData;

        return $this;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function setFrom(Address $from): self
    {
        $this->from = $from;

        return $this;
    }

    public function addTag(string $tag): self
    {
        $this->tags[] = $tag;

        return $this;
    }

    public function addMetadata(string $key, string $metadata): self
    {
        $this->metadata[$key] = $metadata;

        return $this;
    }

    public function trackOpenings(bool $trackOpenings = true): self
    {
        $this->shouldTrackOpenings = $trackOpenings;

        return $this;
    }

    public function shouldTrackAllLinks(): self
    {
        $this->clickTrackingType = ClickTracking::HTML_AND_TEXT;

        return $this;
    }

    public function trackTextLinksOnly(): self
    {
        $this->clickTrackingType = ClickTracking::TEXT_ONLY;

        return $this;
    }

    public function trackHtmlLinksOnly(): self
    {
        $this->clickTrackingType = ClickTracking::HTML_ONLY;

        return $this;
    }

    public function setLinkTracking(ClickTracking $clickTracking): self
    {
        $this->clickTrackingType = $clickTracking;

        return $this;
    }

    public function setDeliveryTime(CarbonInterface $deliveryTime): self
    {
        $this->deliveryTime = $deliveryTime;

        return $this;
    }

    public function setMessageStream(string $messageStream): self
    {
        $this->messageStream = $messageStream;

        return $this;
    }

    /** @return array<int, Address> */
    public function recipients(): array
    {
        return $this->to;
    }

    public function from(): Address
    {
        return $this->from;
    }

    public function html(): string
    {
        return $this->html;
    }

    public function text(): ?string
    {
        return $this->text;
    }

    /** @return array<int, string> */
    public function tags(): array
    {
        return $this->tags;
    }

    public function firstTag(): string
    {
        return $this->tags()[0];
    }

    /** @return array<int, Address> */
    public function replyTo(): array
    {
        return $this->replyTo;
    }

    /** @return array<int, \Junges\BatchMailer\Mailables\Address> */
    public function cc(): array
    {
        return $this->cc;
    }

    /** @return array<int, \Junges\BatchMailer\Mailables\Address> */
    public function bcc(): array
    {
        return $this->bcc;
    }

    /** @return array<string, mixed> */
    public function headers(): array
    {
        return $this->headers;
    }

    /** @return array<int, \Illuminate\Mail\Attachment> */
    public function attachments(): array
    {
        return array_merge($this->attachments, $this->rawAttachments);
    }

    public function getPreparedAttachments(): array
    {
        return $this->prepareAttachments();
    }

    private function prepareAttachments(): array
    {
        return collect([...$this->attachments, ...$this->rawAttachments])
            ->map(static function (array|DataPart $attachment) {
                if (is_array($attachment)) {
                    return $attachment;
                }

                $filename = sprintf(
                    "%s/%s.%s",
                    config('batch-mailer.attachments_temp_path'),
                    time(),
                    $attachment->getMediaSubtype()
                );

                Storage::disk('local')->put($filename, $attachment->getBody());

                return [
                    'attachment' =>  storage_path("app/$filename"),
                    'options' => [
                        'mime' => $attachment->getMediaType(),
                        'as' => $attachment->getFilename(),
                    ],
                ];
            })->all();
    }

    /** @return array<int, string> */
    public function campaignIds(): array
    {
        return $this->campaignIds;
    }

    public function subject(): string
    {
        return $this->subject;
    }

    public function messageStream(): ?string
    {
        return $this->messageStream;
    }

    public function clickTrackingType(): ClickTracking
    {
        return $this->clickTrackingType;
    }

    public function deliveryTime(): ?CarbonInterface
    {
        return $this->deliveryTime;
    }

    public function hasText(): bool
    {
        return ! empty($this->text);
    }

    public function hasTag(): bool
    {
        return ! empty($this->tag);
    }

    public function hasHeaders(): bool
    {
        return ! empty($this->headers);
    }

    public function shouldTrackOpenings(): bool
    {
        return $this->shouldTrackOpenings;
    }

    public function shouldNotTrackOpenings(): bool
    {
        return ! $this->shouldTrackOpenings();
    }

    public function hasReplyToAddresses(): bool
    {
        return ! empty($this->replyTo);
    }

    public function hasDeliveryTime(): bool
    {
        return $this->deliveryTime !== null;
    }

    /** @return array<string, int|float|string> */
    public function metadata(): array
    {
        return $this->metadata;
    }
}
