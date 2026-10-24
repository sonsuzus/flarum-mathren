<?php

/*
 * This file is part of MathRen.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TheTurk\MathRen\Helpers;

use Flarum\Settings\SettingsRepositoryInterface;
use Illuminate\Support\Str;

class Util
{
    public const EXPRESSION_PLACEHOLDER = '%e%';

    protected string $prefix = 'the-turk-mathren.';

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * This method will make prefixed calls easier.
     */
    public function get(string $key, string $default = ''): string
    {
        return (string) ($this->settings->get($this->prefix.$key, $default) ?? $default);
    }

    /**
     * KaTeX options those to be used through this extension.
     */
    public function getKatexOptions(): array
    {
        return [
            'fleqn'            => \boolval($this->get('enable_fleqn')),
            'leqno'            => \boolval($this->get('enable_leqno')),
            'output'           => $this->get('output_mode'),
            'throwOnError'     => \boolval($this->get('throw_on_error')),
            'errorColor'       => $this->get('error_color'),
            'minRuleThickness' => \floatval($this->get('min_rule_thickness')),
            'maxSize'          => \floatval($this->get('max_size')),
            'maxExpand'        => \intval($this->get('max_expand')),
            'macros'           => \json_decode('{'.$this->get('macros').'}'),
            'colorIsTextColor' => \boolval($this->get('color_is_text_color')),
        ];
    }

    /**
     * Create a classes array that will be used to wrap expressions.
     */
    public function getClasses(): array
    {
        return [
            'block'  => 'mathren-block',
            'inline' => 'mathren-inline',
        ];
    }

    /**
     * Create a delimiters array that categorized by their type.
     */
    private function _getDelimitersByType(): array
    {
        return [
            'block'            => $this->_commaToArray($this->get('block_delimiters')),
            'blockAscii'       => $this->_commaToArray($this->get('block_asciimath_delimiters')),
            'inline'           => $this->_commaToArray($this->get('inline_delimiters')),
            'inlineAscii'      => $this->_commaToArray($this->get('inline_asciimath_delimiters')),
            'aliasBlock'       => $this->_commaToArray($this->get('alias_block_delimiters')),
            'aliasBlockAscii'  => $this->_commaToArray($this->get('alias_block_asciimath_delimiters')),
            'aliasInline'      => $this->_commaToArray($this->get('alias_inline_delimiters')),
            'aliasInlineAscii' => $this->_commaToArray($this->get('alias_inline_asciimath_delimiters')),
        ];
    }

    /**
     * This function creates an array of delimiters to be used in various places.
     */
    public function getDelimitersWithOptions(string $type = '', bool $isAsciiMathDelimiter = false): array
    {
        $categorizedDelimiters = $this->_getDelimitersByType();
        $delimitersWithOptions = [];

        $desiredKeys = match ($type) {
            'bbcode' => $isAsciiMathDelimiter ? ['blockAscii', 'inlineAscii'] : ['block', 'inline'],
            'alias'  => $isAsciiMathDelimiter ? ['aliasBlockAscii', 'aliasInlineAscii'] : ['aliasBlock', 'aliasInline'],
            default  => $isAsciiMathDelimiter
                ? ['blockAscii', 'aliasBlockAscii', 'inlineAscii', 'aliasInlineAscii']
                : ['block', 'inline', 'aliasBlock', 'aliasInline'],
        };

        foreach ($categorizedDelimiters as $key => $delimiterArray) {
            if (!\in_array($key, $desiredKeys, true)) {
                continue;
            }

            $displayMode = \in_array($key, ['block', 'blockAscii', 'aliasBlock', 'aliasBlockAscii'], true);

            foreach ($delimiterArray as $delimiter) {
                $delimitersWithOptions = \array_merge(
                    $delimitersWithOptions,
                    $this->_setOptions($delimiter, $displayMode, $isAsciiMathDelimiter)
                );
            }

            if ($type === 'alias') {
                $environments = ['equation', 'align', 'alignat', 'gather', 'CD'];

                foreach ($environments as $environment) {
                    $delimitersWithOptions[] = [
                        'left'    => '\\\begin{'.$environment.'}',
                        'right'   => '\\\end{'.$environment.'}',
                        'display' => true,
                        'ascii'   => false,
                    ];
                }
            }
        }

        return $delimitersWithOptions;
    }

    /**
     * Creates delimiter list with options (left, right, display).
     */
    private function _setOptions(string $syntax, bool $displayMode = false, bool $asciiMath = false): array
    {
        $left = Str::before($syntax, self::EXPRESSION_PLACEHOLDER);
        $right = Str::after($syntax, self::EXPRESSION_PLACEHOLDER);

        return [
            [
                'left'    => $left,
                'right'   => $right,
                'display' => $displayMode,
                'ascii'   => $asciiMath,
            ],
        ];
    }

    /**
     * Converts comma seperated list into an array.
     */
    private function _commaToArray(string $list): array
    {
        $r = [];

        if (!empty($list)) {
            $r = \array_filter(\preg_split('/[\s,]+/', \trim($list)) ?: []);
        }

        return \array_values(\array_unique($r));
    }
}