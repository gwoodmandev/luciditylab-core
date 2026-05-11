<?php

namespace luciditylab\craftThemeEditor\models;

use craft\base\Model;

/**
 * ThemeSettings Model
 *
 * Holds the site-wide theme configuration. Only the active preset key is
 * stored; the colour values for each preset live in ThemeService.
 */
class ThemeSettings extends Model
{
    /** @var string The active theme preset key */
    public string $activePreset = 'default';

    public function rules(): array
    {
        return [
            [['activePreset'], 'string'],
            [['activePreset'], 'in', 'range' => ['default', 'dark', 'midnight', 'warm']],
            [['activePreset'], 'default', 'value' => 'default'],
        ];
    }
}
