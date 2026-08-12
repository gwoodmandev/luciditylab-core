<?php
namespace luciditylab\craftFormBuilder\assets;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

class SubmissionsAsset extends AssetBundle
{
    public $depends = [
        CpAsset::class,
    ];

    public function init(): void
    {
        $this->sourcePath = '@luciditylab/craftFormBuilder/assets';

        $this->js = [
            'js/submissions.js',
            'js/tab-badge.js',
        ];

        $this->css = [
            'css/submissions.css',
        ];

        parent::init();
    }
}
