<?php
namespace luciditylab\craftHeadingTagField\extensions;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class HeadingTagTwigExtension extends AbstractExtension
{
    public function getFunctions(): array
    {
        return [
            new TwigFunction('headingTag', [$this, 'renderHeadingTag'], ['is_safe' => ['html']]),
        ];
    }

    public function renderHeadingTag(array $value, string $class = ''): string
    {
        $text = $value['text'] ?? '';
        $tag  = $value['tag'] ?? '';

        if (empty($text) || empty($tag)) {
            return '';
        }

        $classAttr = !empty($class) ? " class=\"{$class}\"" : '';

        return "<{$tag}{$classAttr}>{$text}</{$tag}>";
    }
}