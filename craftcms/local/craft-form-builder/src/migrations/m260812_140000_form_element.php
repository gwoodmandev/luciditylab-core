<?php
namespace luciditylab\craftFormBuilder\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use DateTime;
use luciditylab\craftFormBuilder\elements\Form;

class m260812_140000_form_element extends Migration
{
    public function safeUp(): bool
    {
        $entryTypeId = (new Query())
            ->select(['id'])
            ->from(Table::ENTRYTYPES)
            ->where(['handle' => 'formEntry'])
            ->scalar($this->db);

        if (!$entryTypeId) {
            echo "    > no formEntry entry type found; nothing to convert\n";

            return true;
        }

        $layout = (new Query())
            ->select(['fl.config'])
            ->from(['et' => Table::ENTRYTYPES])
            ->innerJoin(['fl' => Table::FIELDLAYOUTS], '[[fl.id]] = [[et.fieldLayoutId]]')
            ->where(['et.id' => $entryTypeId])
            ->one($this->db);

        if ($layout && !empty($layout['config'])) {
            $existing = (new Query())
                ->select(['id'])
                ->from(Table::FIELDLAYOUTS)
                ->where(['type' => Form::class, 'dateDeleted' => null])
                ->scalar($this->db);

            if ($existing) {
                echo "    > a " . Form::class . " layout already exists; leaving it alone\n";
            } else {
                $now = Db::prepareDateForDb(new DateTime());

                $this->insert(Table::FIELDLAYOUTS, [
                    'type' => Form::class,
                    'config' => $layout['config'],
                    'dateCreated' => $now,
                    'dateUpdated' => $now,
                    'uid' => StringHelper::UUID(),
                ]);

                echo "    > copied the formEntry field layout to " . Form::class . "\n";
            }
        }

        $formIds = (new Query())
            ->select(['e.id'])
            ->from(['en' => Table::ENTRIES])
            ->innerJoin(['e' => Table::ELEMENTS], '[[e.id]] = [[en.id]]')
            ->where(['en.typeId' => $entryTypeId])
            ->column($this->db);

        if (!$formIds) {
            echo "    > no form entries to convert\n";

            return true;
        }

        foreach ($formIds as $id) {
            $this->update(Table::ELEMENTS, ['type' => Form::class], ['id' => $id]);
        }

        $this->delete(Table::ENTRIES, ['id' => $formIds]);

        echo '    > converted ' . count($formIds) . " form(s) to Form elements\n";

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260812_140000_form_element cannot be reverted automatically.\n";
        echo "Restore from the backup taken before this migration.\n";

        return false;
    }
}
