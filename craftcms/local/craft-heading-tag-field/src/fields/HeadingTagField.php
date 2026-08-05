<?php
namespace luciditylab\craftHeadingTagField\fields;

use Craft;
use craft\base\Field;
use luciditylab\craftHeadingTagField\assets\HeadingTagFieldAsset;

class HeadingTagField extends Field
{
    // default heading tag applied when no tag has been selected yet
    public const DEFAULT_TAG = 'h2';

    public static function displayName(): string
    {
        return 'Heading Tag';
    }

    public function normalizeValue(mixed $value, ?\craft\base\ElementInterface $element = null): mixed
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!is_array($value)) {
            $value = [];
        }

        return [
            // fall back to the default tag so new fields arrive pre-selected
            'tag' => !empty($value['tag']) ? $value['tag'] : self::DEFAULT_TAG,
            'text' => $value['text'] ?? '',
        ];
    }

    public function serializeValue(mixed $value, ?\craft\base\ElementInterface $element = null): mixed
    {
        return json_encode($value);
    }

    public function getElementValidationRules(): array
    {
        $rules = [];

        // only validate on the live scenario, not when auto-saving drafts
        $rules[] = [
            'validateHeadingTag',
            'on' => [\craft\base\Element::SCENARIO_LIVE],
        ];

        return $rules;
    }

    public function validateHeadingTag(\craft\base\Element $element): void
    {
        $value = $element->getFieldValue($this->handle);
        $text = $value['text'] ?? '';
        $tag = $value['tag'] ?? '';

        if ($this->required && empty($text)) {
            $element->addError("field:{$this->handle}", 'This field is required.');
            return;
        }

        if (!empty($text) && empty($tag)) {
            $element->addError("field:{$this->handle}", 'Please select a heading tag.');
        }
    }

    public function getInputHtml(mixed $value, ?\craft\base\ElementInterface $element = null): string
    {
        // available heading tag options
        $options = ['h1', 'h2', 'h3', 'h4', 'p', 'span'];

        // extract tag and text from the saved value, defaulting the tag to H2
        $tagValue = !empty($value['tag']) ? $value['tag'] : self::DEFAULT_TAG;
        $textValue = $value['text'] ?? '';

        // get the Craft view instance and register the field's CSS and JS assets
        $view = Craft::$app->getView();
        $view->registerAssetBundle(HeadingTagFieldAsset::class);

        // build input names using the field handle so Craft can map them on save
        $textName = "{$this->handle}[text]";
        $tagName  = "{$this->handle}[tag]";

        // build the field HTML
        $html = '<div class="heading-tag-field">';

        // visible text input
        $html .= "<input
            type='text'
            class='text fullwidth htf-text'
            placeholder='Enter your heading...'
            value='" . htmlspecialchars($textValue) . "'
            name='{$textName}'
        >";

        // tag selector buttons
        $html .= '<div class="htf-buttons">';
        foreach ($options as $option) {
            $active = $tagValue === $option ? 'btn submit' : 'btn';
            $html .= "<button type='button' class='{$active}' data-value='{$option}'>" . ucfirst($option) . "</button>";
        }
        $html .= '</div>';

        // hidden input to store the selected tag value for form submission
        $html .= "<input type='hidden' class='htf-hidden-tag' name='{$tagName}' value='" . htmlspecialchars($tagValue) . "'>";

        $html .= '</div>';

        return $html;
    }
}