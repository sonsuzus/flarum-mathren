<?php

declare(strict_types=1);

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
    public const PREFIX = 'the-turk-mathren.';
    public const EXPRESSION_PLACEHOLDER = '%e%';

    /**
     * Default extension settings (without prefix).
     *
     * @var array<string, string>
     */
    public const DEFAULTS = [
        'alias_block_delimiters'            => '$$%e%$$, \[%e%\]',
        'alias_inline_delimiters'           => '\(%e%\)',
        'block_delimiters'                  => '[math]%e%[/math]',
        'inline_delimiters'                 => '[imath]%e%[/imath]',
        'alias_block_asciimath_delimiters'  => '\$%e%\$',
        'alias_inline_asciimath_delimiters' => '\{%e%\}',
        'block_asciimath_delimiters'        => '[asmath]%e%[/asmath]',
        'inline_asciimath_delimiters'       => '[iasmath]%e%[/iasmath]',
        'color_is_text_color'               => '0',
        'enable_copy_tex'                   => '1',
        'enable_fleqn'                      => '0',
        'enable_leqno'                      => '0',
        'enable_editor_buttons'             => '1',
        'throw_on_error'                    => '0',
        'error_color'                       => '#cc0000',
        'macros'                            => '',
        'max_expand'                        => '1000',
        'max_size'                          => '10',
        'min_rule_thickness'                => '0.05',
        'output_mode'                       => 'htmlAndMathml',
        'aliases_as_primary'                => '1',
        'allow_asciimath'                   => '0',
        'cdn_katex'                         => 'https://cdn.jsdelivr.net/npm/katex@0.16.0/dist/katex.min.js',
        'sri_katex'                         => 'sha384-X/XCfMm41VSsqRNQgDerQczD69XqmjOOOwYQvr/uuC+j4OPoNhVgjdGFwhvN02Ja',
        'cdn_asciimath2tex'                 => 'https://unpkg.com/asciimath2tex@1.4.0/dist/asciimath2tex.umd.js',
        'sri_asciimath2tex'                 => 'sha384-cdvLGvItf6Jz+kIS7cNq6cThfMF6hSoUveHAZJBnJqmEosxD2lUHn2/pUmGKGybo',
    ];

    /**
     * Fallback primary delimiters by category key.
     *
     * @var array<string, array{left: string, right: string, display: bool, ascii: bool}>
     */
    private const FALLBACK_PRIMARY_DELIMITERS = [
        'block'            => ['left' => '[math]', 'right' => '[/math]', 'display' => true, 'ascii' => false],
        'inline'           => ['left' => '[imath]', 'right' => '[/imath]', 'display' => false, 'ascii' => false],
        'blockAscii'       => ['left' => '[asmath]', 'right' => '[/asmath]', 'display' => true, 'ascii' => true],
        'inlineAscii'      => ['left' => '[iasmath]', 'right' => '[/iasmath]', 'display' => false, 'ascii' => true],
        'aliasBlock'       => ['left' => '$$', 'right' => '$$', 'display' => true, 'ascii' => false],
        'aliasInline'      => ['left' => '\(', 'right' => '\)', 'display' => false, 'ascii' => false],
        'aliasBlockAscii'  => ['left' => '\$', 'right' => '\$', 'display' => true, 'ascii' => true],
        'aliasInlineAscii' => ['left' => '\{', 'right' => '\}', 'display' => false, 'ascii' => true],
    ];

    public function __construct(
        protected SettingsRepositoryInterface $settings
    ) {
    }

    /**
     * Retrieve a setting value with automatic fallback to extension defaults.
     */
    public function get(string $key, ?string $default = null): string
    {
        $fallback = $default ?? (self::DEFAULTS[$key] ?? '');
        $value = $this->settings->get(self::PREFIX.$key);

        if ($value === null || ($value === '' && $key !== 'macros' && isset(self::DEFAULTS[$key]))) {
            return (string) $fallback;
        }

        return (string) $value;
    }

    /**
     * Retrieve a boolean setting value safely.
     */
    public function getBool(string $key): bool
    {
        return \filter_var($this->get($key), \FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * KaTeX options to be used throughout the extension.
     *
     * @return array<string, mixed>
     */
    public function getKatexOptions(): array
    {
        return [
            'fleqn'            => $this->getBool('enable_fleqn'),
            'leqno'            => $this->getBool('enable_leqno'),
            'output'           => $this->get('output_mode'),
            'throwOnError'     => $this->getBool('throw_on_error'),
            'errorColor'       => $this->get('error_color'),
            'minRuleThickness' => (float) $this->get('min_rule_thickness'),
            'maxSize'          => (float) $this->get('max_size'),
            'maxExpand'        => (int) $this->get('max_expand'),
            'macros'           => $this->parseMacros(),
            'colorIsTextColor' => $this->getBool('color_is_text_color'),
        ];
    }

    /**
     * CSS classes used to wrap rendered math expressions.
     *
     * @return array{block: string, inline: string}
     */
    public function getClasses(): array
    {
        return [
            'block'  => 'mathren-block',
            'inline' => 'mathren-inline',
        ];
    }

    /**
     * Get the full list of delimiters for the Forum API (`bbcode` or `alias`),
     * automatically merging AsciiMath delimiters when `allow_asciimath` is enabled.
     *
     * @return list<array{left: string, right: string, display: bool, ascii: bool}>
     */
    public function getForumDelimiters(string $type): array
    {
        $delimiters = $this->getDelimitersWithOptions($type, false);

        if ($this->getBool('allow_asciimath')) {
            $delimiters = \array_merge(
                $delimiters,
                $this->getDelimitersWithOptions($type, true)
            );
        }

        return \array_values($delimiters);
    }

    /**
     * Get the primary (first) delimiter object for a specific delimiter category.
     *
     * @return array{left: string, right: string, display: bool, ascii: bool}
     */
    public function getPrimaryDelimiter(string $categoryKey): array
    {
        $categorized = $this->getDelimitersByType();
        $list = $categorized[$categoryKey] ?? [];

        $displayMode = \in_array($categoryKey, ['block', 'blockAscii', 'aliasBlock', 'aliasBlockAscii'], true);
        $asciiMath = \in_array($categoryKey, ['blockAscii', 'inlineAscii', 'aliasBlockAscii', 'aliasInlineAscii'], true);

        foreach ($list as $syntax) {
            $parsed = $this->setOptions($syntax, $displayMode, $asciiMath);
            if (!empty($parsed[0])) {
                return $parsed[0];
            }
        }

        return self::FALLBACK_PRIMARY_DELIMITERS[$categoryKey] ?? self::FALLBACK_PRIMARY_DELIMITERS['inline'];
    }

    /**
     * Create an array of delimiters with options (`left`, `right`, `display`, `ascii`).
     *
     * @return list<array{left: string, right: string, display: bool, ascii: bool}>
     */
    public function getDelimitersWithOptions(string $type = '', bool $isAsciiMathDelimiter = false): array
    {
        $categorizedDelimiters = $this->getDelimitersByType();
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
                    $this->setOptions($delimiter, $displayMode, $isAsciiMathDelimiter)
                );
            }
        }

        if ($type === 'alias' && !$isAsciiMathDelimiter) {
            $environments = ['equation', 'align', 'alignat', 'gather', 'CD'];

            foreach ($environments as $environment) {
                $delimitersWithOptions[] = [
                    'left'    => '\\begin{'.$environment.'}',
                    'right'   => '\\end{'.$environment.'}',
                    'display' => true,
                    'ascii'   => false,
                ];
            }
        }

        return \array_values($delimitersWithOptions);
    }

    /**
     * Categorize raw delimiter lists by their type.
     *
     * @return array<string, list<string>>
     */
    private function getDelimitersByType(): array
    {
        return [
            'block'            => $this->commaToArray($this->get('block_delimiters')),
            'blockAscii'       => $this->commaToArray($this->get('block_asciimath_delimiters')),
            'inline'           => $this->commaToArray($this->get('inline_delimiters')),
            'inlineAscii'      => $this->commaToArray($this->get('inline_asciimath_delimiters')),
            'aliasBlock'       => $this->commaToArray($this->get('alias_block_delimiters')),
            'aliasBlockAscii'  => $this->commaToArray($this->get('alias_block_asciimath_delimiters')),
            'aliasInline'      => $this->commaToArray($this->get('alias_inline_delimiters')),
            'aliasInlineAscii' => $this->commaToArray($this->get('alias_inline_asciimath_delimiters')),
        ];
    }

    /**
     * Parse a single delimiter syntax string (`left%e%right`) into an option entry.
     *
     * @return list<array{left: string, right: string, display: bool, ascii: bool}>
     */
    private function setOptions(string $syntax, bool $displayMode = false, bool $asciiMath = false): array
    {
        if (!\str_contains($syntax, self::EXPRESSION_PLACEHOLDER)) {
            return [];
        }

        $left = Str::before($syntax, self::EXPRESSION_PLACEHOLDER);
        $right = Str::after($syntax, self::EXPRESSION_PLACEHOLDER);

        if ($left === '' || $right === '') {
            return [];
        }

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
     * Convert a comma/whitespace-separated delimiter string into a unique list.
     *
     * @return list<string>
     */
    private function commaToArray(string $list): array
    {
        $trimmed = \trim($list);
        if ($trimmed === '') {
            return [];
        }

        $items = \array_filter(\preg_split('/[\s,]+/', $trimmed) ?: []);

        return \array_values(\array_unique($items));
    }

    /**
     * Safely decode custom KaTeX macros into a JSON object.
     */
    private function parseMacros(): object
    {
        $raw = \trim($this->get('macros'));
        if ($raw === '') {
            return new \stdClass();
        }

        $json = \str_starts_with($raw, '{') ? $raw : '{'.$raw.'}';
        $decoded = \json_decode($json);

        return \is_object($decoded) ? $decoded : new \stdClass();
    }
}