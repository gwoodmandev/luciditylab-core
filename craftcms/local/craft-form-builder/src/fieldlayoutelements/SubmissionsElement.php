<?php
namespace luciditylab\craftFormBuilder\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use craft\fieldlayoutelements\BaseUiElement;
use craft\helpers\Html;
use craft\web\View;
use luciditylab\craftFormBuilder\assets\SubmissionsAsset;
use luciditylab\craftFormBuilder\elements\Form;
use luciditylab\craftFormBuilder\FormBuilderPlugin;

class SubmissionsElement extends BaseUiElement
{
    public int $perPage = 25;

    protected function selectorLabel(): string
    {
        return Craft::t('form-builder', 'Submissions');
    }

    protected function selectorIcon(): ?string
    {
        return 'envelope';
    }

    public function formHtml(?ElementInterface $element = null, bool $static = false): ?string
    {
        // on a brand-new form there is nothing to list yet
        if (!$element instanceof Form || !$element->id) {
            return Html::tag('div', Html::tag(
                'p',
                Craft::t('form-builder', 'Submissions will appear here once this form has been saved and has started receiving messages.'),
                ['class' => 'light']
            ), ['class' => 'fb-submissions']);
        }

        $plugin = FormBuilderPlugin::getInstance();
        $submissions = $plugin->submissions->getAllByFormId($element->id, $this->perPage);
        $total = $plugin->submissions->getTotalByFormId($element->id);
        $unread = $plugin->submissions->getUnreadCountByFormId($element->id);

        $view = Craft::$app->getView();
        $view->registerAssetBundle(SubmissionsAsset::class);

        return $view->renderTemplate('form-builder/_cp/submissions', [
            'form' => $element,
            'submissions' => $submissions,
            'total' => $total,
            'unread' => $unread,
            'perPage' => $this->perPage,
            'definitions' => $plugin->forms->getFieldDefinitions($element),
            'badgeLabel' => Craft::t('form-builder', 'Submissions'),
        ], View::TEMPLATE_MODE_CP);
    }
}
