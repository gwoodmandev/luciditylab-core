<?php
namespace luciditylab\craftFormBuilder\variables;

use Craft;
use luciditylab\craftFormBuilder\elements\Form;
use craft\helpers\Template;
use craft\web\View;
use luciditylab\craftFormBuilder\FormBuilderPlugin;
use Twig\Markup;

/**
 * Exposes the form builder to Twig as craft.formBuilder.
 */
class FormBuilderVariable
{
    /**
     * Renders a complete form, including any validation errors or success
     * message left over from a previous submission.
     *
     * {{ craft.formBuilder.render(block.form.one()) }}
     */
    public function render(?Form $form, array $options = []): Markup
    {
        if (!$form) {
            return Template::raw('');
        }

        // the form's own styles travel with it, so any template that renders a
        // form gets them without having to remember
        $this->registerAssets();

        $plugin = FormBuilderPlugin::getInstance();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

        // errors + previously submitted values are passed back via route params
        $errors = $request->getParam('formBuilderErrors')[$form->id] ?? [];
        $values = $request->getParam('formBuilderValues')[$form->id] ?? [];

        $flash = $session->getFlash('formBuilderSuccess');
        $success = (is_array($flash) && ($flash['formId'] ?? null) === $form->id)
            ? (string)($flash['message'] ?? '')
            : null;

        $html = Craft::$app->getView()->renderTemplate('form-builder/_form', [
            'form' => $form,
            'definitions' => $plugin->forms->getFieldDefinitions($form),
            'errors' => $errors,
            'values' => $values,
            'success' => $success,
            'options' => $options,
            // signed so the spam check can trust the render time
            'timestamp' => Craft::$app->getSecurity()->hashData((string)time()),
        ], View::TEMPLATE_MODE_SITE);

        return Template::raw($html);
    }

    /**
     * Registers the form component's stylesheet through the asset registry.
     *
     * Registration only needs to happen before the page template finishes
     * rendering, which is why calling it mid-render works.
     */
    private function registerAssets(): void
    {
        $assetRegistry = Craft::$app->getPlugins()->getPlugin('asset-registry');

        // the plugin is optional — a site without it simply loads the CSS
        // some other way, and rendering should not break
        if (!$assetRegistry || !$assetRegistry->has('assetRegistry')) {
            return;
        }

        $assetRegistry->assetRegistry->registerStylesheet('component', 'form');
    }

    /**
     * The normalised field definitions for a form, if a template wants to
     * build its own markup instead.
     */
    public function fields(?Form $form): array
    {
        if (!$form) {
            return [];
        }

        return FormBuilderPlugin::getInstance()->forms->getFieldDefinitions($form);
    }
}
