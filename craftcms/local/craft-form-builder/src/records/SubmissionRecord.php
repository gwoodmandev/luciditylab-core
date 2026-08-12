<?php
namespace luciditylab\craftFormBuilder\records;

use craft\db\ActiveRecord;

class SubmissionRecord extends ActiveRecord
{
    public const TABLE = '{{%formbuilder_submissions}}';

    public static function tableName(): string
    {
        return self::TABLE;
    }
}
