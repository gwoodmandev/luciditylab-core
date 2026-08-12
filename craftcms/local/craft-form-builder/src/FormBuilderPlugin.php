<?php
namespace luciditylab\craftFormBuilder;

use Craft;
use craft\base\Plugin;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\RegisterComponentTypesEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;
use craft\models\FieldLayout;
use craft\services\Elements;
use craft\services\Fields;
use craft\services\UserPermissions;
use craft\web\UrlManager;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;
use luciditylab\craftFormBuilder\elements\Form;
use luciditylab\craftFormBuilder\fieldlayoutelements\SubmissionsElement;
use luciditylab\craftFormBuilder\fields\FormsField;
use luciditylab\craftFormBuilder\services\FormsService;
use luciditylab\craftFormBuilder\services\SubmissionsService;
use luciditylab\craftFormBuilder\variables\FormBuilderVariable;
use yii\base\Event;

class FormBuilderPlugin extends Plugin
{
    public string $schemaVersion = '1.1.0';

    public bool $hasCpSection = true;

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'forms' => FormsService::class,
            'submissions' => SubmissionsService::class,
        ]);

        $this->registerTemplateRoots();
        $this->registerElementType();
        $this->registerFieldType();
        $this->registerLayoutElement();
        $this->registerCpRoutes();
        $this->registerPermissions();
        $this->registerTwigVariable();
        $this->registerTranslations();
    }

    public function getCpNavItem(): ?array
    {
        $user = Craft::$app->getUser();

        if (!$user->getIsAdmin() && !$user->checkPermission('formBuilder:viewForms')) {
            return null;
        }

        $item = parent::getCpNavItem();
        $item['label'] = Craft::t('form-builder', 'Forms');
        $item['url'] = 'form-builder/forms';

        $subnav = [
            'forms' => [
                'label' => Craft::t('form-builder', 'Forms'),
                'url' => 'form-builder/forms',
            ],
        ];

        // the field layout designer is an admin concern
        if ($user->getIsAdmin()) {
            $subnav['settings'] = [
                'label' => Craft::t('form-builder', 'Settings'),
                'url' => 'form-builder/settings',
            ];
        }

        $item['subnav'] = $subnav;

        return $item;
    }

    private function registerElementType(): void
    {
        Event::on(
            Elements::class,
            Elements::EVENT_REGISTER_ELEMENT_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = Form::class;
            }
        );
    }

    private function registerFieldType(): void
    {
        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = FormsField::class;
            }
        );
    }

    private function registerTemplateRoots(): void
    {
        $root = __DIR__ . '/templates';

        foreach ([View::EVENT_REGISTER_CP_TEMPLATE_ROOTS, View::EVENT_REGISTER_SITE_TEMPLATE_ROOTS] as $event) {
            Event::on(
                View::class,
                $event,
                function(RegisterTemplateRootsEvent $e) use ($root) {
                    $e->roots['form-builder'] = $root;
                }
            );
        }
    }

    private function registerLayoutElement(): void
    {
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_UI_ELEMENTS,
            function(DefineFieldLayoutElementsEvent $event) {
                $layout = $event->sender;

                if ($layout->type !== Form::class) {
                    return;
                }

                $event->elements[] = SubmissionsElement::class;
            }
        );
    }

    private function registerCpRoutes(): void
    {
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function(RegisterUrlRulesEvent $event) {
                $event->rules['form-builder'] = 'form-builder/forms/index';
                $event->rules['form-builder/forms'] = 'form-builder/forms/index';
                $event->rules['form-builder/forms/new'] = 'form-builder/forms/create';
                $event->rules['form-builder/forms/<elementId:\d+>'] = 'elements/edit';
                $event->rules['form-builder/settings'] = 'form-builder/settings/index';
                $event->rules['form-builder/settings/field-layout'] = 'form-builder/settings/save-field-layout';
            }
        );
    }

    private function registerPermissions(): void
    {
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function(RegisterUserPermissionsEvent $event) {
                $event->permissions[] = [
                    'heading' => Craft::t('form-builder', 'Forms'),
                    'permissions' => [
                        'formBuilder:viewForms' => [
                            'label' => Craft::t('form-builder', 'View forms and submissions'),
                            'nested' => [
                                'formBuilder:saveForms' => [
                                    'label' => Craft::t('form-builder', 'Create and edit forms'),
                                ],
                                'formBuilder:deleteForms' => [
                                    'label' => Craft::t('form-builder', 'Delete forms and submissions'),
                                ],
                            ],
                        ],
                    ],
                ];
            }
        );
    }

    private function registerTwigVariable(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                $variable = $event->sender;
                $variable->set('formBuilder', FormBuilderVariable::class);
            }
        );
    }

    private function registerTranslations(): void
    {
        // the plugin ships no translation files; fall back to the source string
        Craft::$app->getI18n()->translations['form-builder'] ??= [
            'class' => \yii\i18n\PhpMessageSource::class,
            'sourceLanguage' => 'en',
            'basePath' => __DIR__ . '/translations',
            'forceTranslation' => true,
            'allowOverrides' => true,
        ];
    }
}
