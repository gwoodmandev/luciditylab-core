<?php
namespace luciditylab\craftAssetRegistry\services;

use yii\base\Component;

class AssetRegistryService extends Component
{
    private array $stylesheets = [];
    private array $scripts = [];

    public function registerStylesheet(string $category, string $name): void
    {
        $this->stylesheets["{$category}:{$name}"] = "{$category}/{$name}";
    }

    public function registerScript(string $category, string $name): void
    {
        $this->scripts["{$category}:{$name}"] = "{$category}/{$name}";
    }

    public function getStylesheets(): array
    {
        return array_values($this->stylesheets);
    }

    public function getScripts(): array
    {
        return array_values($this->scripts);
    }
}