<?php
namespace luciditylab\craftFormBuilder\migrations;

use craft\db\Migration;
use craft\db\Table;
use luciditylab\craftFormBuilder\records\SubmissionRecord;

class Install extends Migration
{
    public function safeUp(): bool
    {
        if ($this->db->tableExists(SubmissionRecord::TABLE)) {
            return true;
        }

        $this->createTable(SubmissionRecord::TABLE, [
            'id' => $this->primaryKey(),
            'formId' => $this->integer()->notNull(),
            'siteId' => $this->integer()->notNull(),
            'payload' => $this->longText()->notNull(),
            'ipAddress' => $this->string(45),
            'userAgent' => $this->text(),
            'dateRead' => $this->dateTime(),
            'dateCreated' => $this->dateTime()->notNull(),
            'dateUpdated' => $this->dateTime()->notNull(),
            'uid' => $this->uid(),
        ]);

        // listing a form's submissions newest-first is the hot query
        $this->createIndex(null, SubmissionRecord::TABLE, ['formId', 'dateCreated']);

        // unread badge counts filter on this
        $this->createIndex(null, SubmissionRecord::TABLE, ['formId', 'dateRead']);

        // clean up submissions when the form element or site is deleted
        $this->addForeignKey(null, SubmissionRecord::TABLE, ['formId'], Table::ELEMENTS, ['id'], 'CASCADE', null);
        $this->addForeignKey(null, SubmissionRecord::TABLE, ['siteId'], Table::SITES, ['id'], 'CASCADE', 'CASCADE');

        return true;
    }

    public function safeDown(): bool
    {
        $this->dropTableIfExists(SubmissionRecord::TABLE);

        return true;
    }
}
