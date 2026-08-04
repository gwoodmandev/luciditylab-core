<?php
namespace luciditylab\craftAssetRegistry;

use Craft;
use craft\base\Plugin;
use craft\web\View;
use craft\events\TemplateEvent;
use luciditylab\craftAssetRegistry\services\AssetRegistryService;
use luciditylab\craftAssetRegistry\extensions\AssetRegistryTwigExtension;
use yii\base\Event;

class AssetRegistryPlugin extends Plugin
{
    public function init(): void
    {
        parent::init();

        $this->setComponents([
            'assetRegistry' => AssetRegistryService::class,
        ]);

        Craft::$app->view->registerTwigExtension(new AssetRegistryTwigExtension());

        Event::on(
            View::class,
            View::EVENT_AFTER_RENDER_PAGE_TEMPLATE,
            function (TemplateEvent $event) {
                $theme = Craft::$app->getGlobals()->getSetByHandle('configuration')->theme ?? 'default';
                $registry = $this->assetRegistry;

                $styleTags = '';
                foreach ($registry->getStylesheets() as $path) {
                    $styleTags .= "<link rel=\"stylesheet\" href=\"/assets/css/themes/{$theme}/{$path}.css\">\n";
                }

                $scriptTags = '';
                foreach ($registry->getScripts() as $path) {
                    $scriptTags .= "<script type=\"module\" src=\"/assets/js/{$path}.js\"></script>\n";
                }

                $event->output = str_replace('<!--REGISTERED_STYLES-->', $styleTags, $event->output);
                $event->output = str_replace('<!--REGISTERED_SCRIPTS-->', $scriptTags, $event->output);
            }
        );
    }
}