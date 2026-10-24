<?php

/*
 * This file is part of MathRen.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TheTurk\MathRen;

use Flarum\Api\Schema;
use Flarum\Settings\SettingsRepositoryInterface;

/**
 * MathRen ayarlarını forum API kaynağına (ForumResource) alan olarak ekler.
 *
 * Flarum 2.x'te ApiResource::fields() callback'i argümansız çağrılır
 * ve Schema alanlarından oluşan bir dizi döndürmelidir.
 */
class LoadSettings
{
    private const PREFIX = 'the-turk-mathren.';

    /**
     * Ayar anahtarı (prefix'siz) => tip (string|bool|int|float)
     */
    private const SETTINGS = [
        'alias_block_delimiters' => 'string',
        'alias_inline_delimiters' => 'string',
        'block_delimiters' => 'string',
        'inline_delimiters' => 'string',
        'alias_block_asciimath_delimiters' => 'string',
        'alias_inline_asciimath_delimiters' => 'string',
        'block_asciimath_delimiters' => 'string',
        'inline_asciimath_delimiters' => 'string',
        'color_is_text_color' => 'bool',
        'enable_copy_tex' => 'bool',
        'enable_fleqn' => 'bool',
        'enable_leqno' => 'bool',
        'enable_editor_buttons' => 'bool',
        'throw_on_error' => 'bool',
        'error_color' => 'string',
        'max_expand' => 'int',
        'max_size' => 'float',
        'min_rule_thickness' => 'float',
        'output_mode' => 'string',
        'aliases_as_primary' => 'bool',
        'allow_asciimath' => 'bool',
        'cdn_katex' => 'string',
        'sri_katex' => 'string',
        'cdn_asciimath2tex' => 'string',
        'sri_asciimath2tex' => 'string',
    ];

    public function __construct(
        private readonly SettingsRepositoryInterface $settings
    ) {
    }

    public function __invoke(): array
    {
        $fields = [];

        foreach (self::SETTINGS as $key => $type) {
            $fields[] = $this->makeField($type, $this->attributeName($key))
                ->get(fn () => $this->readValue($key, $type));
        }

        return $fields;
    }

    /**
     * JS tarafında app.forum.attribute('...') ile okunacak ad.
     * Frontend kodun hangi adı bekliyorsa burayı ona göre uyarla.
     */
    private function attributeName(string $key): string
    {
        return self::PREFIX.$key;
    }

    private function makeField(string $type, string $name)
    {
        return match ($type) {
            'bool' => Schema\Boolean::make($name),
            'int', 'float' => Schema\Number::make($name),
            default => Schema\Str::make($name),
        };
    }

    private function readValue(string $key, string $type): bool|int|float|string
    {
        $value = $this->settings->get(self::PREFIX.$key);

        return match ($type) {
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'int' => (int) $value,
            'float' => (float) $value,
            default => (string) $value,
        };
    }
}