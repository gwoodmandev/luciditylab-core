<?php

namespace luciditylab\craftThemeEditor\services;

use craft\base\Component;
use luciditylab\craftThemeEditor\ThemeEditorPlugin;

class ThemeService extends Component
{
    /**
     * All built-in theme presets, keyed by their preset key. Each preset has:
     *  - vars: a map of CSS custom-property name => value, applied at :root.
     *  - extraCss: a raw CSS string for selectors where Craft hardcodes light-
     *    theme values (var(--white) backgrounds, light gray fills, etc.) that
     *    can't be fixed by overriding a variable alone.
     *
     * 'default' is intentionally empty — it leaves Craft's stock CP styling
     * untouched.
     */
    public function getPresets(): array
    {
        return [
            'default' => [
                'vars'     => [],
                'extraCss' => '',
            ],

            'dark' => [
                'vars' => [
                    '--gray-050-hsl' => '210, 10%, 12%',
                    '--gray-100-hsl' => '0, 0%, 15%',
                    '--gray-150-hsl' => '210, 8%, 18%',
                    '--gray-200-hsl' => '210, 8%, 22%',
                    '--gray-300-hsl' => '210, 8%, 28%',
                    '--gray-350-hsl' => '210, 9%, 32%',
                    '--gray-400-hsl' => '210, 10%, 38%',
                    '--gray-500-hsl' => '210, 12%, 46%',
                    '--gray-550-hsl' => '210, 14%, 52%',
                    '--gray-600-hsl' => '210, 16%, 58%',
                    '--gray-700-hsl' => '210, 18%, 68%',
                    '--gray-800-hsl' => '210, 20%, 78%',
                    '--gray-900-hsl' => '210, 22%, 88%',
                    '--gray-1000-hsl' => '210, 25%, 95%',

                    '--white' => '#ffffff',
                    '--black' => '#000000',

                    '--blue-600' => '#3b82f6',
                    '--blue-700' => '#2563eb',
                    '--sky-600'  => '#0ea5e9',

                    '--pane-bg'     => 'hsl(210, 8%, 20%)',
                    '--pane-shadow' => '0 1px 0 hsla(0, 0%, 0%, 0.5), 0 4px 12px hsla(0, 0%, 0%, 0.35)',
                ],
                'extraCss' => self::overrideCssForDark('hsl(210, 8%, 20%)', 'hsla(0, 0%, 0%, 0.5)'),
            ],

            'midnight' => [
                'vars' => [
                    '--gray-050-hsl' => '220, 25%, 10%',
                    '--gray-100-hsl' => '222, 28%, 12%',
                    '--gray-150-hsl' => '222, 28%, 15%',
                    '--gray-200-hsl' => '222, 25%, 18%',
                    '--gray-300-hsl' => '220, 22%, 24%',
                    '--gray-350-hsl' => '220, 20%, 30%',
                    '--gray-400-hsl' => '220, 18%, 38%',
                    '--gray-500-hsl' => '220, 16%, 48%',
                    '--gray-600-hsl' => '220, 14%, 58%',
                    '--gray-700-hsl' => '220, 14%, 70%',
                    '--gray-800-hsl' => '220, 16%, 80%',
                    '--gray-900-hsl' => '220, 18%, 90%',
                    '--gray-1000-hsl' => '220, 20%, 96%',

                    '--blue-500' => '#4f7cff',
                    '--blue-600' => '#3b5fff',
                    '--blue-700' => '#2f4bff',

                    '--indigo-500' => '#6366f1',
                    '--indigo-600' => '#4f46e5',
                    '--indigo-700' => '#4338ca',

                    '--sky-600' => '#38bdf8',
                    '--sky-700' => '#0ea5e9',

                    '--pane-bg'     => 'hsl(222, 25%, 17%)',
                    '--pane-shadow' => '0 1px 0 hsla(220, 40%, 4%, 0.6), 0 6px 16px hsla(220, 40%, 4%, 0.45)',
                ],
                'extraCss' => self::overrideCssForDark('hsl(222, 25%, 17%)', 'hsla(220, 40%, 4%, 0.6)'),
            ],

            'warm' => [
                'vars' => [
                    '--gray-050-hsl' => '35, 25%, 96%',
                    '--gray-100-hsl' => '35, 20%, 92%',
                    '--gray-150-hsl' => '35, 18%, 88%',
                    '--gray-200-hsl' => '35, 16%, 84%',
                    '--gray-300-hsl' => '35, 14%, 70%',
                    '--gray-350-hsl' => '35, 12%, 62%',
                    '--gray-400-hsl' => '35, 12%, 54%',
                    '--gray-500-hsl' => '35, 14%, 44%',
                    '--gray-600-hsl' => '35, 16%, 38%',
                    '--gray-700-hsl' => '35, 18%, 30%',
                    '--gray-800-hsl' => '35, 20%, 24%',
                    '--gray-900-hsl' => '35, 22%, 18%',
                    '--gray-1000-hsl' => '35, 24%, 12%',

                    '--amber-500'  => '#f59e0b',
                    '--amber-600'  => '#d97706',
                    '--orange-500' => '#f97316',
                    '--orange-600' => '#ea580c',

                    '--yellow-500' => '#eab308',
                    '--yellow-600' => '#ca8a04',

                    '--red-600' => '#dc2626',

                    '--pane-bg'     => 'hsl(35, 30%, 98%)',
                    '--pane-shadow' => '0 1px 0 hsla(30, 20%, 50%, 0.10), 0 4px 12px hsla(30, 25%, 35%, 0.10)',
                ],
                // Warm is still a light theme — the only override needed is
                // tinting the selected tab bg to match the warm pane.
                'extraCss' => self::overrideCssForLight('hsl(35, 30%, 98%)'),
            ],
        ];
    }

    /**
     * CSS overrides for dark themes — targets every selector where Craft
     * hardcodes a white or near-white surface that breaks on dark UI.
     *
     * @param string $paneBg The pane background colour for this theme.
     * @param string $shadow A subtle shadow colour matching the theme.
     */
    private static function overrideCssForDark(string $paneBg, string $shadow): string
    {
        return <<<CSS

/* Selected tab — Craft hardcodes var(--white), match the pane instead */
.pane-tabs [role='tablist'] [role='tab'].sel {
    background-color: {$paneBg} !important;
}

/* Drag helper backgrounds while reordering table rows */
.datatablesorthelper,
.editabletablesorthelper {
    background-color: {$paneBg};
}

/* Element index source-path: Craft compiles `\$white` into the CSS so
   `--white` overrides can't reach it. Force the area to the pane colour. */
.element-index .source-path .chevron-btns {
    background: {$paneBg};
}
.element-index .source-path .chevron-btns::before,
.element-index .source-path .chevron-btns::after {
    border-block-start-color: {$paneBg};
    border-block-end-color: {$paneBg};
}
.element-index .source-path .btn.settings {
    box-shadow: 0 0 0 2px {$paneBg};
}

/* Draft / content-notice icons: white halo looks like a bright disc on dark */
.draft-notice .draft-icon,
.content-notice .content-notice-icon {
    box-shadow: 0 1px 1px 1px {$paneBg};
}

/* Activity avatars use a white ring around the thumb */
.activity-container ul li .activity-btn .elementthumb {
    border-color: {$paneBg};
}

/* Auto-suggest dropdown — hardcoded var(--white) */
.autosuggest__results-container {
    background-color: {$paneBg};
}

/* Form controls: checkbox & radio fills are hsl(212deg 50% 99%) literal */
input.checkbox + label::before,
div.checkbox::before,
input.radio + label::before,
div.radio::before {
    background-color: var(--gray-150) !important;
}

/* Year picker pill in the date picker header */
.ui-datepicker-title select.ui-datepicker-year {
    background-color: var(--gray-200);
}

/* Busy/loading overlay for element indexes — Craft compiles white-with-alpha,
   so we paint a dark veil over the top with the same effect. */
.elements.busy::after {
    background: hsla(0, 0%, 0%, 0.35) !important;
}

/* Element-selector progress shade — same compiled-white issue */
.elementselect .progress-shade {
    background-color: hsla(0, 0%, 0%, 0.35) !important;
}

/* Icon picker modal loading veil — hardcoded rgb(255 255 255 / 75%) */
.icon-picker-modal .body .icon-picker-modal--list.loading::after {
    background-color: hsla(0, 0%, 0%, 0.5) !important;
}

/* Floating button-fade controls (image preview etc.) — inverts on dark */
.button-fade .buttons .btn {
    background-color: var(--gray-300) !important;
    color: var(--gray-1000) !important;
}
.button-fade .buttons .btn:hover,
.button-fade .buttons .btn:focus {
    background-color: var(--gray-400) !important;
}

/* Tip / warning panes — the *-050 background tokens are pre-baked light
   tints. Tone them down so they don't glow on dark themes. */
.pane.tip {
    background-color: hsla(199, 89%, 48%, 0.10) !important;
}
.pane.warning {
    background-color: hsla(38, 92%, 50%, 0.10) !important;
}
.meta.warning {
    background-color: hsla(48, 96%, 53%, 0.10) !important;
}

CSS;
    }

    /**
     * Light-theme override — currently only needs to align the selected tab
     * background with the warm pane colour.
     */
    private static function overrideCssForLight(string $paneBg): string
    {
        return <<<CSS

.pane-tabs [role='tablist'] [role='tab'].sel {
    background-color: {$paneBg} !important;
}

CSS;
    }

    /**
     * Return the active preset's vars + extraCss as a single struct.
     */
    public function getActiveTheme(): array
    {
        $settings = ThemeEditorPlugin::getInstance()->getSettings();
        $presets  = $this->getPresets();

        return $presets[$settings->activePreset] ?? $presets['default'];
    }

    /**
     * Build the full CSS payload for a preset: `:root { ... }` block plus any
     * extra rules for selectors Craft hardcodes.
     */
    public function buildCssVariables(array $theme): string
    {
        $vars     = $theme['vars'] ?? [];
        $extraCss = $theme['extraCss'] ?? '';

        if (empty($vars) && $extraCss === '') {
            return '';
        }

        $css = '';

        if (!empty($vars)) {
            $css .= ":root {\n";
            foreach ($vars as $token => $value) {
                $css .= "    {$token}: {$value};\n";
            }
            $css .= "}\n";
            $css .= "body { transition: background-color .2s ease, color .2s ease; }\n";
        }

        if ($extraCss !== '') {
            $css .= $extraCss;
        }

        return $css;
    }
}
