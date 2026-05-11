<?php
namespace luciditylab\craftHeadingTagField;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterComponentTypesEvent;
use craft\services\Fields;
use luciditylab\craftHeadingTagField\fields\HeadingTagField;
use luciditylab\craftHeadingTagField\extensions\HeadingTagTwigExtension;
use yii\base\Event;

class HeadingTagFieldPlugin extends \craft\base\Plugin
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