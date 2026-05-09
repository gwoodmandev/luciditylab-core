<?php
namespace luciditylab\craftHeadingTagField;

use luciditylab\craftHeadingTagField\fields\HeadingTagField;
use luciditylab\craftHeadingTagField\extensions\HeadingTagTwigExtension;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use yii\base\Event;

class Plugin extends \craft\base\Plugin
{
    public function init(): void
    {
        parent::init();

        // register the twig extension
        \Craft::$app->view->registerTwigExtension(new HeadingTagTwigExtension());

        Event::on(
            Fields::class,
            Fields::EVENT_REGISTER_FIELD_TYPES,
            function(RegisterComponentTypesEvent $event) {
                $event->types[] = HeadingTagField::class;
            }
        );
    }
}