<?php
namespace luciditylab\craftFormBuilder;

use Craft;
use craft\base\Plugin;
use craft\events\DefineFieldLayoutElementsEvent;
use craft\events\RegisterTemplateRootsEvent;
use craft\models\FieldLayout;
use craft\web\View;
use craft\web\twig\variables\CraftVariable;
use luciditylab\craftFormBuilder\fieldlayoutelements\SubmissionsElement;
use luciditylab\craftFormBuilder\services\FormsService;
use luciditylab\craftFormBuilder\services\SubmissionsService;
use luciditylab\craftFormBuilder\variables\FormBuilderVariable;
use yii\base\Event;

/**
 * @property-read FormsService $forms
 * @property-read SubmissionsService $submissions
 */
class FormBuilderPlugin extends Plugin
{
    public string $schemaVersion = '1.0.0';

    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'forms' => FormsService::class,
            'submissions' => SubmissionsService::class,
        ]);

        $this->registerTemplateRoots();
        $this->registerLayoutElement();
        $this->registerTwigVariable();
        $this->registerTranslations();
    }

    /**
     * Makes the plugin's templates resolvable as form-builder/... in both
     * the CP (submission list/detail) and the site (email bodies, form render).
     */
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

    /**
     * Offers the Submissions element in the field layout designer, so it can
     * be dropped onto the Form entry type as its own tab.
     */
    private function registerLayoutElement(): void
    {
        Event::on(
            FieldLayout::class,
            FieldLayout::EVENT_DEFINE_UI_ELEMENTS,
            function(DefineFieldLayoutElementsEvent $event) {
                /** @var FieldLayout $layout */
                $layout = $event->sender;

                // only relevant on entry layouts
                if ($layout->type !== \craft\elements\Entry::class) {
                    return;
                }

                $event->elements[] = SubmissionsElement::class;
            }
        );
    }

    private function registerTwigVariable(): void
    {
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function(Event $event) {
                /** @var CraftVariable $variable */
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
