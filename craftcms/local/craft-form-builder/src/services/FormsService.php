<?php
namespace luciditylab\craftFormBuilder\services;

use Craft;
use luciditylab\craftFormBuilder\elements\Form;
use craft\helpers\StringHelper;
use yii\base\Component;

class FormsService extends Component
{

    private const INPUT_TYPES = [
        'formFieldText',
        'formFieldTextarea',
        'formFieldDropdown',
        'formFieldRadio',
        'formFieldCheckbox',
        'formFieldConsent',
        'formFieldFile',
        'formFieldHidden',
    ];

    public function getFieldDefinitions(Form $form): array
    {
        $definitions = [];
        $usedHandles = [];

        foreach ($form->formFields->all() as $block) {
            $type = $block->type->handle;

            // section headings carry no handle and collect nothing
            if (!in_array($type, self::INPUT_TYPES, true)) {
                $definitions[] = [
                    'type' => $type,
                    'isInput' => false,
                    'heading' => $block->heading ?? null,
                    'bodyText' => $block->bodyText ?? null,
                ];
                continue;
            }

            $handle = $this->resolveHandle($block, $usedHandles);
            $usedHandles[] = $handle;

            $definitions[] = [
                'type' => $type,
                'isInput' => true,
                'handle' => $handle,
                'label' => (string)($block->formFieldLabel ?? ''),
                'required' => (bool)($block->formFieldRequired ?? false),
                'instructions' => $block->formFieldInstructions ?? null,
                'placeholder' => $block->formFieldPlaceholder ?? null,
                'width' => $block->formFieldWidth ?? 'full',
                'inputType' => $block->formFieldInputType ?? 'text',
                'maxLength' => $block->formFieldMaxLength ?? null,
                'rows' => $block->formFieldRows ?? 5,
                'options' => $this->normaliseOptions($block->formFieldOptions ?? null),
                'allowMultiple' => (bool)($block->formFieldAllowMultiple ?? false),
                'defaultValue' => $block->formFieldDefaultValue ?? null,
                'fileTypes' => $this->normaliseFileTypes($block->formFieldAllowedFileTypes ?? null),
                'maxFileSize' => (int)($block->formFieldMaxFileSize ?? 5),
                'consentText' => $block->formFieldConsentText ?? null,
            ];
        }

        return $definitions;
    }

    private function resolveHandle($block, array $usedHandles): string
    {
        $handle = trim((string)($block->formFieldHandle ?? ''));

        if ($handle === '') {
            $handle = StringHelper::toKebabCase((string)($block->formFieldLabel ?? ''));
        } else {
            $handle = StringHelper::toKebabCase($handle);
        }

        if ($handle === '') {
            $handle = 'field-' . $block->id;
        }

        // ensure uniqueness within the form
        $base = $handle;
        $i = 2;
        while (in_array($handle, $usedHandles, true)) {
            $handle = "{$base}-{$i}";
            $i++;
        }

        return $handle;
    }

    private function normaliseOptions(?array $rows): array
    {
        if (!$rows) {
            return [];
        }

        $options = [];

        foreach ($rows as $row) {
            $label = trim((string)($row['label'] ?? ''));
            $value = trim((string)($row['value'] ?? ''));

            if ($label === '' && $value === '') {
                continue;
            }

            // let editors fill in only the label and have the value follow
            $options[] = [
                'label' => $label !== '' ? $label : $value,
                'value' => $value !== '' ? $value : $label,
                'default' => !empty($row['default']),
            ];
        }

        return $options;
    }

    private function normaliseFileTypes(?string $raw): array
    {
        if (!$raw) {
            return [];
        }

        $types = preg_split('/[\s,]+/', strtolower($raw), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_map(fn(string $t) => ltrim($t, '.'), $types));
    }

    public function interpolate(string $subject, array $payload, Form $form): string
    {
        return preg_replace_callback('/\{([a-z0-9\-_]+)\}/i', function(array $m) use ($payload, $form) {
            $key = $m[1];

            if ($key === 'form') {
                return $form->title;
            }

            $value = $payload[$key] ?? '';

            return is_array($value) ? implode(', ', $value) : (string)$value;
        }, $subject) ?? $subject;
    }

    public function getRecipients(Form $form): array
    {
        $raw = (string)($form->recipients ?? '');
        $lines = preg_split('/[\r\n,;]+/', $raw, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        $valid = [];

        foreach ($lines as $line) {
            $address = trim($line);

            if ($address !== '' && filter_var($address, FILTER_VALIDATE_EMAIL)) {
                $valid[] = $address;
            } elseif ($address !== '') {
                Craft::warning("Form Builder: skipping invalid recipient \"{$address}\" on form {$form->id}", __METHOD__);
            }
        }

        return array_values(array_unique($valid));
    }
}
