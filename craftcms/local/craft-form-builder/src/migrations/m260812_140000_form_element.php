<?php
namespace luciditylab\craftFormBuilder\migrations;

use craft\db\Migration;
use craft\db\Query;
use craft\db\Table;
use craft\helpers\Db;
use craft\helpers\StringHelper;
use DateTime;
use luciditylab\craftFormBuilder\elements\Form;

/**
 * Converts formEntry entries into Form elements so forms live in their own
 * control panel section rather than under Entries.
 *
 * The conversion is deliberately in place:
 *
 *  - the elements row keeps its id and only changes type, so submissions
 *    (which reference formId) and any relations keep resolving
 *  - the existing field layout is retargeted rather than rebuilt, so every
 *    layout element UID survives and the content JSON stays readable
 *  - Matrix blocks bind to their owner by id with no element-type constraint,
 *    so the nested form-field blocks follow the element across
 */
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

        // Copy the layout rather than retarget it. Deleting the formEntry entry
        // type from project config calls deleteLayoutById() on its layout
        // (Entries.php:1712), which would take the retargeted layout with it and
        // orphan every stored value. A copy keeps the same element UIDs — which
        // is what the content JSON is keyed by — while being independently owned.
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

        // drop the entries rows last: while they exist the elements would show
        // up in both Entries and Forms
        $this->delete(Table::ENTRIES, ['id' => $formIds]);

        echo '    > converted ' . count($formIds) . " form(s) to Form elements\n";

        // the entry type and its section are removed via project config, not
        // here, so the change is captured for other environments

        return true;
    }

    public function safeDown(): bool
    {
        echo "m260812_140000_form_element cannot be reverted automatically.\n";
        echo "Restore from the backup taken before this migration.\n";

        return false;
    }
}
