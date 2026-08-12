<?php
namespace luciditylab\craftFormBuilder\elements\db;

use craft\elements\db\ElementQuery;

/**
 * Form elements keep all of their settings in custom fields on the element
 * type's field layout, so there is no settings table to join — the base
 * ElementQuery behaviour is sufficient.
 *
 * @method \luciditylab\craftFormBuilder\elements\Form[]|array all($db = null)
 * @method \luciditylab\craftFormBuilder\elements\Form|array|null one($db = null)
 */
class FormQuery extends ElementQuery
{
    protected array $defaultOrderBy = ['elements_sites.title' => SORT_ASC];
}
