<?php
namespace luciditylab\craftFormBuilder\variables;

use Craft;
use craft\elements\Entry;
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
    public function render(?Entry $form, array $options = []): Markup
    {
        if (!$form) {
            return Template::raw('');
        }

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
     * The normalised field definitions for a form, if a template wants to
     * build its own markup instead.
     */
    public function fields(?Entry $form): array
    {
        if (!$form) {
            return [];
        }

        return FormBuilderPlugin::getInstance()->forms->getFieldDefinitions($form);
    }
}
