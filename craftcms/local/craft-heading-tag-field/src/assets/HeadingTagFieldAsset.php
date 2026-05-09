<?php
namespace luciditylab\craftHeadingTagField\assets;

use craft\web\AssetBundle;

class HeadingTagFieldAsset extends AssetBundle
{
    public function init(): void
    {
        $this->sourcePath = '@luciditylab/craftHeadingTagField/assets';

        $this->js = [
            'js/heading-tag-field.js',
        ];

        $this->css = [
            'css/heading-tag-field.css',
        ];

        parent::init();
    }
}