<?php
namespace luciditylab\craftFormBuilder\elements;

use Craft;
use craft\base\Element;
use craft\elements\conditions\ElementConditionInterface;
use craft\elements\db\ElementQueryInterface;
use craft\elements\User;
use craft\helpers\Html;
use craft\helpers\UrlHelper;
use craft\models\FieldLayout;
use luciditylab\craftFormBuilder\elements\db\FormQuery;
use luciditylab\craftFormBuilder\FormBuilderPlugin;

class Form extends Element
{
    // ============================================= //
    // Identity
    // ============================================= //

    public static function displayName(): string
    {
        return Craft::t('form-builder', 'Form');
    }

    public static function pluralDisplayName(): string
    {
        return Craft::t('form-builder', 'Forms');
    }

    public static function lowerDisplayName(): string
    {
        return Craft::t('form-builder', 'form');
    }

    public static function pluralLowerDisplayName(): string
    {
        return Craft::t('form-builder', 'forms');
    }

    public static function refHandle(): ?string
    {
        return 'form';
    }

    // ============================================= //
    // Capabilities
    // ============================================= //

    public static function hasTitles(): bool
    {
        return true;
    }

    public static function hasUris(): bool
    {
        return false;
    }

    public static function isLocalized(): bool
    {
        return false;
    }

    public static function hasStatuses(): bool
    {
        return true;
    }

    // ============================================= //
    // Query
    // ============================================= //

    public static function find(): ElementQueryInterface
    {
        return new FormQuery(static::class);
    }

    public static function createCondition(): ElementConditionInterface
    {
        return Craft::createObject(\craft\elements\conditions\ElementCondition::class, [static::class]);
    }

    // ============================================= //
    // Index
    // ============================================= //

    protected static function defineSources(string $context): array
    {
        return [
            [
                'key' => '*',
                'label' => Craft::t('form-builder', 'All forms'),
                'defaultSort' => ['title', 'asc'],
            ],
        ];
    }

    protected static function defineFieldLayouts(?string $source): array
    {
        $layout = Craft::$app->getFields()->getLayoutByType(self::class);

        return $layout ? [$layout] : [];
    }

    protected static function defineTableAttributes(): array
    {
        return [
            'title' => ['label' => Craft::t('app', 'Title')],
            'recipients' => ['label' => Craft::t('form-builder', 'Recipients')],
            'submissions' => ['label' => Craft::t('form-builder', 'Submissions')],
            'dateCreated' => ['label' => Craft::t('app', 'Date Created')],
            'dateUpdated' => ['label' => Craft::t('app', 'Date Updated')],
            'id' => ['label' => Craft::t('app', 'ID')],
            'uid' => ['label' => Craft::t('app', 'UID')],
        ];
    }

    protected static function defineDefaultTableAttributes(string $source): array
    {
        return ['recipients', 'submissions', 'dateUpdated'];
    }

    protected static function defineSortOptions(): array
    {
        return [
            'title' => Craft::t('app', 'Title'),
            'dateCreated' => Craft::t('app', 'Date Created'),
            'dateUpdated' => Craft::t('app', 'Date Updated'),
        ];
    }

    protected static function defineSearchableAttributes(): array
    {
        return ['title', 'recipients'];
    }

    protected function attributeHtml(string $attribute): string
    {
        switch ($attribute) {
            case 'recipients':
                $recipients = FormBuilderPlugin::getInstance()->forms->getRecipients($this);

                return $recipients
                    ? Html::encode(implode(', ', $recipients))
                    : '<span class="light">' . Craft::t('form-builder', 'None set') . '</span>';

            case 'submissions':
                if (!$this->id) {
                    return '';
                }

                $submissions = FormBuilderPlugin::getInstance()->submissions;
                $total = $submissions->getTotalByFormId($this->id);
                $unread = $submissions->getUnreadCountByFormId($this->id);

                if (!$total) {
                    return '<span class="light">0</span>';
                }

                $label = (string)$total;

                if ($unread) {
                    $label .= ' ' . Html::tag('span', (string)$unread, [
                        'class' => 'badge',
                        'title' => Craft::t('form-builder', '{n} unread', ['n' => $unread]),
                    ]);
                }

                return $label;
        }

        return parent::attributeHtml($attribute);
    }

    // ============================================= //
    // Field Layout
    // ============================================= //

    public function getFieldLayout(): ?FieldLayout
    {
        // one install-wide layout, keyed on this element class
        return Craft::$app->getFields()->getLayoutByType(self::class);
    }

    // ============================================= //
    // URLs
    // ============================================= //

    protected function cpEditUrl(): ?string
    {
        return UrlHelper::cpUrl("form-builder/forms/$this->id");
    }

    public function getPostEditUrl(): ?string
    {
        return UrlHelper::cpUrl('form-builder/forms');
    }

    protected function crumbs(): array
    {
        return [
            [
                'label' => Craft::t('form-builder', 'Forms'),
                'url' => UrlHelper::cpUrl('form-builder/forms'),
            ],
        ];
    }

    // ============================================= //
    // Permissions
    // ============================================= //

    public function canView(User $user): bool
    {
        if (parent::canView($user)) {
            return true;
        }

        return $user->can('formBuilder:viewForms');
    }

    public function canSave(User $user): bool
    {
        if (parent::canSave($user)) {
            return true;
        }

        return $user->can('formBuilder:saveForms');
    }

    public function canDuplicate(User $user): bool
    {
        if (parent::canDuplicate($user)) {
            return true;
        }

        return $user->can('formBuilder:saveForms');
    }

    public function canDelete(User $user): bool
    {
        if (parent::canDelete($user)) {
            return true;
        }

        return $user->can('formBuilder:deleteForms');
    }

}
