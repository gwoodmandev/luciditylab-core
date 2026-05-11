<?php
namespace luciditylab\craftThemeEditor;

use Craft;
use craft\base\Plugin;
use craft\events\RegisterTemplateRootsEvent;
use craft\helpers\UrlHelper;
use craft\web\View;
use luciditylab\craftThemeEditor\assetbundles\ThemeEditorAsset;
use luciditylab\craftThemeEditor\models\ThemeSettings;
use luciditylab\craftThemeEditor\services\ThemeService;
use yii\base\Event;

/**
 * @property-read ThemeService $themeService
 */
class ThemeEditorPlugin extends Plugin
{
    public string $schemaVersion = '1.3.0';

    public bool $hasCpSettings = true;
    public bool $hasCpSection = false;

    public static function config(): array
    {
        return [
            'components' => [
                'themeService' => ThemeService::class,
            ],
        ];
    }

    public function init(): void
    {
        parent::init();

        // ---------------------------------------------------------------------
        // Register the CP template root explicitly. Craft auto-registers one
        // matching the plugin handle, but in some installs the handle resolves
        // to the package directory name ("craft-theme-editor") instead of the
        // composer "extra.handle" value ("theme-editor"). Registering both
        // makes the templates discoverable regardless.
        // ---------------------------------------------------------------------
        Event::on(
            View::class,
            View::EVENT_REGISTER_CP_TEMPLATE_ROOTS,
            function (RegisterTemplateRootsEvent $event) {
                $templatesPath = __DIR__ . '/../templates';
                $event->roots['theme-editor'] = $templatesPath;
                $event->roots['craft-theme-editor'] = $templatesPath;
            }
        );

        // ---------------------------------------------------------------------
        // Inject the active theme's CSS variables on every CP request.
        // ---------------------------------------------------------------------
        if (Craft::$app->getRequest()->getIsCpRequest()) {
            Event::on(
                View::class,
                View::EVENT_BEFORE_RENDER_TEMPLATE,
                function () {
                    $theme = $this->themeService->getActiveTheme();
                    $css   = $this->themeService->buildCssVariables($theme);

                    if ($css !== '') {
                        Craft::$app->getView()->registerCss($css, [], 'theme-editor-vars');
                    }
                }
            );
        }
    }

    protected function createSettingsModel(): ThemeSettings
    {
        return new ThemeSettings();
    }

    /**
     * Override the default settings response so we render our own full CP
     * template, with an explicit form, CSRF input, and Save button. This is
     * more reliable than relying on Craft's settingsHtml() auto-form wrapper.
     */
    public function getSettingsResponse(): mixed
    {
        $view = Craft::$app->getView();
        $view->registerAssetBundle(ThemeEditorAsset::class);

        return Craft::$app->controller->renderTemplate('theme-editor/settings', [
            'plugin'   => $this,
            'settings' => $this->getSettings(),
            'presets'  => $this->themeService->getPresets(),
        ]);
    }
}
