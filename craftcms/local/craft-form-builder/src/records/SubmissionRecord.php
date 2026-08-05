<?php
namespace luciditylab\craftFormBuilder\records;

use craft\db\ActiveRecord;

/**
 * Active record for a single form submission.
 *
 * @property int $id
 * @property int $formId       the Form entry this submission belongs to
 * @property int $siteId
 * @property string $payload   JSON-encoded submitted values
 * @property string|null $ipAddress
 * @property string|null $userAgent
 * @property string|null $dateRead  null means unread
 */
class SubmissionRecord extends ActiveRecord
{
    public const TABLE = '{{%formbuilder_submissions}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
