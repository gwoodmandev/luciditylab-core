<?php
namespace luciditylab\craftAssetRegistry\extensions;

use Craft;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AssetRegistryTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('registerStylesheet', [$this, 'registerStylesheet']),
            new TwigFunction('registerScript', [$this, 'registerScript']),
            new TwigFunction('renderRegisteredStyles', fn() => '<!--REGISTERED_STYLES-->', ['is_safe' => ['html']]),
            new TwigFunction('renderRegisteredScripts', fn() => '<!--REGISTERED_SCRIPTS-->', ['is_safe' => ['html']]),
        ];
    }

    public function registerStylesheet(string $category, string $name): void
    {
        Craft::$app->plugins->getPlugin('asset-registry')->assetRegistry->registerStylesheet($category, $name);
    }

    public function registerScript(string $category, string $name): void
    {
        Craft::$app->plugins->getPlugin('asset-registry')->assetRegistry->registerScript($category, $name);
    }
}