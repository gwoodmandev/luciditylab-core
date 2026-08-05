<?php
namespace luciditylab\craftFormBuilder\models;

use craft\base\Model;
use DateTime;

/**
 * A single submitted message, hydrated from a SubmissionRecord.
 */
class Submission extends Model
{
    public ?int $id = null;
    public ?int $formId = null;
    public ?int $siteId = null;

    /** @var array<string, mixed> submitted values keyed by field handle */
    public array $payload = [];

    public ?string $ipAddress = null;
    public ?string $userAgent = null;
    public ?DateTime $dateRead = null;
    public ?DateTime $dateCreated = null;

    public function isRead(): bool
    {
        return $this->dateRead !== null;
    }

    /**
     * A short summary for the submissions list, built from the first
     * meaningful value in the payload.
     */
    public function getSummary(int $length = 60): string
    {
        foreach ($this->payload as $value) {
            if (is_array($value)) {
                $value = implode(', ', $value);
            }

            $value = trim((string)$value);

            if ($value !== '') {
                return mb_strimwidth($value, 0, $length, '…');
            }
        }

        return '(no content)';
    }
}
