<?php
namespace luciditylab\craftFormBuilder\services;

use Craft;
use craft\elements\Entry;
use craft\helpers\DateTimeHelper;
use craft\helpers\Db;
use DateTime;
use luciditylab\craftFormBuilder\models\Submission;
use luciditylab\craftFormBuilder\records\SubmissionRecord;
use yii\base\Component;

class SubmissionsService extends Component
{
    /**
     * Persists a submission against a form entry.
     *
     * @param array<string, mixed> $payload submitted values keyed by field handle
     */
    public function save(Entry $form, array $payload, ?string $ip, ?string $userAgent): Submission
    {
        $record = new SubmissionRecord();
        $record->formId = $form->id;
        $record->siteId = $form->siteId;
        $record->payload = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $record->ipAddress = $ip !== null ? mb_substr($ip, 0, 45) : null;
        $record->userAgent = $userAgent !== null ? mb_substr($userAgent, 0, 500) : null;
        $record->dateRead = null;
        $record->save();

        return $this->toModel($record);
    }

    /**
     * @return Submission[] newest first
     */
    public function getAllByFormId(int $formId, ?int $limit = null, int $offset = 0): array
    {
        $query = SubmissionRecord::find()
            ->where(['formId' => $formId])
            ->orderBy(['dateCreated' => SORT_DESC, 'id' => SORT_DESC])
            ->offset($offset);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return array_map([$this, 'toModel'], $query->all());
    }

    public function getTotalByFormId(int $formId): int
    {
        return (int)SubmissionRecord::find()->where(['formId' => $formId])->count();
    }

    public function getUnreadCountByFormId(int $formId): int
    {
        return (int)SubmissionRecord::find()
            ->where(['formId' => $formId, 'dateRead' => null])
            ->count();
    }

    public function getById(int $id): ?Submission
    {
        $record = SubmissionRecord::findOne(['id' => $id]);

        return $record ? $this->toModel($record) : null;
    }

    public function markAsRead(int $id, bool $read = true): bool
    {
        $record = SubmissionRecord::findOne(['id' => $id]);

        if (!$record) {
            return false;
        }

        $record->dateRead = $read ? Db::prepareDateForDb(new DateTime()) : null;

        return (bool)$record->save(false, ['dateRead', 'dateUpdated']);
    }

    public function markAllAsRead(int $formId): int
    {
        return (int)Craft::$app->getDb()->createCommand()
            ->update(
                SubmissionRecord::TABLE,
                ['dateRead' => Db::prepareDateForDb(new DateTime())],
                ['formId' => $formId, 'dateRead' => null]
            )
            ->execute();
    }

    public function delete(int $id): bool
    {
        $record = SubmissionRecord::findOne(['id' => $id]);

        return $record ? (bool)$record->delete() : false;
    }

    private function toModel(SubmissionRecord $record): Submission
    {
        return new Submission([
            'id' => (int)$record->id,
            'formId' => (int)$record->formId,
            'siteId' => (int)$record->siteId,
            'payload' => json_decode((string)$record->payload, true) ?: [],
            'ipAddress' => $record->ipAddress,
            'userAgent' => $record->userAgent,
            'dateRead' => $record->dateRead ? DateTimeHelper::toDateTime($record->dateRead) : null,
            'dateCreated' => $record->dateCreated ? DateTimeHelper::toDateTime($record->dateCreated) : null,
        ]);
    }
}
