<?php
namespace luciditylab\craftFormBuilder\fields;

use Craft;
use craft\fields\BaseRelationField;
use luciditylab\craftFormBuilder\elements\Form;

class FormsField extends BaseRelationField
{
    public static function displayName(): string
    {
        return Craft::t('form-builder', 'Forms');
    }

    public static function elementType(): string
    {
        return Form::class;
    }

    public static function defaultSelectionLabel(): string
    {
        return Craft::t('form-builder', 'Select a form');
    }

    public static function icon(): string
    {
        return 'envelope';
    }
}
