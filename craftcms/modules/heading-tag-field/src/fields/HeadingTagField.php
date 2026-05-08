<?php
namespace customplugin\HeadingTagField\fields;

use Craft;
use craft\base\Field;

class HeadingTagField extends Field
{
    public static function displayName(): string
    {
        return 'Heading Tag';
    }

    public function getInputHtml(mixed $value, ?\craft\base\ElementInterface $element = null): string
    {
        $options = ['H1', 'H2', 'H3', 'H4', 'P', 'span'];
        $tagValue = $value['tag'] ?? '';
        $textValue = $value['text'] ?? '';

        $html = '<div class="heading-tag-field" style="display:flex;flex-direction:column;gap:8px;">';

        // Text input
        $html .= "<input 
            type='text' 
            class='text fullwidth' 
            placeholder='Enter your heading...' 
            value='" . htmlspecialchars($textValue) . "' 
            id='text-{$this->handle}'
        >";

        // Tag buttons
        $html .= '<div style="display:flex;gap:8px;">';
        foreach ($options as $option) {
            $active = $tagValue === $option ? 'btn submit' : 'btn';
            $html .= "<button type='button' class='{$active}' data-value='{$option}'>" . $option . "</button>";
        }
        $html .= '</div>';

        // Hidden inputs for both values
        $html .= "<input type='hidden' name='{$this->handle}[tag]' value='" . htmlspecialchars($tagValue) . "' id='hidden-tag-{$this->handle}'>";
        $html .= "<input type='hidden' name='{$this->handle}[text]' value='" . htmlspecialchars($textValue) . "' id='hidden-text-{$this->handle}'>";

        $html .= '</div>';

        $html .= "
        <script>
            (function() {
                const wrapper = document.getElementById('text-{$this->handle}').closest('.heading-tag-field');
                const textInput = wrapper.querySelector('#text-{$this->handle}');
                const hiddenText = wrapper.querySelector('#hidden-text-{$this->handle}');
                const hiddenTag = wrapper.querySelector('#hidden-tag-{$this->handle}');

                // Sync text input to hidden field
                textInput.addEventListener('input', () => {
                    hiddenText.value = textInput.value;
                });

                // Handle tag button clicks
                wrapper.querySelectorAll('.btn').forEach(btn => {
                    btn.addEventListener('click', () => {
                        wrapper.querySelectorAll('.btn').forEach(b => b.classList.remove('submit'));
                        btn.classList.add('submit');
                        hiddenTag.value = btn.dataset.value;
                    });
                });
            })();
        </script>";

        return $html;
    }
}
?>