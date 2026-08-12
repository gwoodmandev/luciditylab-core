<?php
namespace luciditylab\craftFormBuilder\fields;

use Craft;
use craft\fields\BaseRelationField;
use luciditylab\craftFormBuilder\elements\Form;

/**
 * Relates Form elements, so a page module can select which form to render.
 *
 * Craft's own relation fields (Entries, Categories, …) hardcode their element
 * type, so a custom element needs its own field.
 */
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
