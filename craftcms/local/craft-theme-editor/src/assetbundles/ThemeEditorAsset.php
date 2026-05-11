<?php
namespace luciditylab\craftThemeEditor\assetbundles;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class ThemeEditorAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = __DIR__ . '/../../resources';

        $this->depends = [
            CpAsset::class,
        ];

        $this->css = [
            'css/theme-editor.css',
        ];

        $this->js = [
            'js/theme-editor.js',
        ];

        parent::init();
    }
}
