<?php
namespace luciditylab\craftFormBuilder\elements\db;

use craft\elements\db\ElementQuery;

class FormQuery extends ElementQuery
{
    protected array $defaultOrderBy = ['elements_sites.title' => SORT_ASC];
}
