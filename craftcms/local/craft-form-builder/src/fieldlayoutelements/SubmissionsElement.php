<?php
namespace luciditylab\craftFormBuilder\fieldlayoutelements;

use Craft;
use craft\base\ElementInterface;
use luciditylab\craftFormBuilder\elements\Form;
use craft\fieldlayoutelements\BaseUiElement;
use craft\helpers\Html;
use craft\helpers\Json;
use craft\web\View;
use luciditylab\craftFormBuilder\FormBuilderPlugin;

/**
 * Renders a form's submissions inside the entry editor, and decorates its
 * own tab with an unread count.
 */
class SubmissionsElement extends BaseUiElement
{
    /**
     * How many submissions to show before paginating.
     */
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
        // On a brand-new form there is nothing to list yet.
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

        $html = $view->renderTemplate('form-builder/_cp/submissions', [
            'form' => $element,
            'submissions' => $submissions,
            'total' => $total,
            'unread' => $unread,
            'perPage' => $this->perPage,
            'definitions' => $plugin->forms->getFieldDefinitions($element),
        ], View::TEMPLATE_MODE_CP);

        // Craft has no API for badging a field-layout tab, so the count is
        // grafted on client-side. Uses Craft's own .badge class so it matches
        // the rest of the CP.
        $this->registerTabBadgeJs($unread);

        return $html;
    }

    /**
     * Finds this element's own tab in the rendered tab bar and appends a
     * badge showing the unread count.
     */
    private function registerTabBadgeJs(int $unread): void
    {
        $label = Json::encode(Craft::t('form-builder', 'Submissions'));
        $count = Json::encode($unread > 99 ? '99+' : (string)$unread);
        $show = $unread > 0 ? 'true' : 'false';

        $js = <<<JS
(() => {
    const show = {$show};
    const label = {$label};
    const count = {$count};

    const decorate = () => {
        // match the tab whose text is our label, ignoring any badge we added
        const tab = Array.from(document.querySelectorAll('#tabs a, .tabs a'))
            .find(a => a.textContent.trim().replace(/\\s*\\d+\\+?$/, '') === label);
        if (!tab) return false;

        // get the label
        const tabLabel = tab.querySelector('.tab-label');
        if (!tabLabel) return false;

        const existing = tab.querySelector('.fb-unread-badge');

        if (!show) {
            existing?.remove();
            return true;
        }

        if (existing) {
            existing.textContent = count;
            return true;
        }

        const badge = document.createElement('span');
        badge.className = 'badge fb-unread-badge';
        badge.textContent = count;
        badge.setAttribute('aria-label', count + ' unread');
        tabLabel.appendChild(badge);

        return true;
    };

    // the tab bar may not exist yet on first paint
    if (!decorate()) {
        const observer = new MutationObserver(() => {
            if (decorate()) observer.disconnect();
        });
        observer.observe(document.body, { childList: true, subtree: true });
        // don't observe forever if the tab never appears
        setTimeout(() => observer.disconnect(), 10000);
    }
})();
JS;

        Craft::$app->getView()->registerJs($js, View::POS_END);
        Craft::$app->getView()->registerCss('.fb-unread-badge { margin-left: 6px; }');
    }
}
