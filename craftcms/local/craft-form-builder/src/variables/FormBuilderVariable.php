<?php
namespace luciditylab\craftFormBuilder\variables;

use Craft;
use luciditylab\craftFormBuilder\elements\Form;
use craft\helpers\Template;
use craft\web\View;
use luciditylab\craftFormBuilder\FormBuilderPlugin;
use Twig\Markup;

class FormBuilderVariable
{
    public function render(?Form $form, array $options = []): Markup
    {
        if (!$form) {
            return Template::raw('');
        }

        $this->registerAssets();

        $plugin = FormBuilderPlugin::getInstance();
        $request = Craft::$app->getRequest();
        $session = Craft::$app->getSession();

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

    private function registerAssets(): void
    {
        $assetRegistry = Craft::$app->getPlugins()->getPlugin('asset-registry');

        if (!$assetRegistry || !$assetRegistry->has('assetRegistry')) {
            return;
        }

        $assetRegistry->assetRegistry->registerStylesheet('component', 'form');
    }

    public function fields(?Form $form): array
    {
        if (!$form) {
            return [];
        }

        return FormBuilderPlugin::getInstance()->forms->getFieldDefinitions($form);
    }
}
