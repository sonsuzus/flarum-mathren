<?php

/*
 * This file is part of MathRen.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TheTurk\MathRen;

use Flarum\Api\Context;
use Flarum\Api\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use TheTurk\MathRen\Helpers\Util;

class LoadSettings
{
    public function __construct(
        protected Util $util
    ) {
    }

    /**
     * Get the setting values from the database and make them available in the forum.
     *
     * @param Context $context
     * @return array<int, Schema\Field>
     */
    public function __invoke(Context $context): array
    {
        $explicitAsciiDelimiters = [];
        $explicitLatexDelimiters = [];

        $allowAsciiMath = \boolval($this->util->get('allow_asciimath'));
        $bbCodeAsciiDelimiters = [];
        $aliasAsciiDelimiters = [];
        $bbCodeLatexDelimiters = $this->util->getDelimitersWithOptions('bbcode');
        $aliasLatexDelimiters = $this->util->getDelimitersWithOptions('alias');

        if ($allowAsciiMath) {
            $bbCodeAsciiDelimiters = $this->util->getDelimitersWithOptions('bbcode', true);
            $aliasAsciiDelimiters = $this->util->getDelimitersWithOptions('alias', true);
            $bbCodeDelimiters = \array_merge($bbCodeAsciiDelimiters, $bbCodeLatexDelimiters);
            $aliasDelimiters = \array_merge($aliasAsciiDelimiters, $aliasLatexDelimiters);
        } else {
            $bbCodeDelimiters = $bbCodeLatexDelimiters;
            $aliasDelimiters = $aliasLatexDelimiters;
        }

        foreach ($bbCodeDelimiters as $delimiter) {
            $item = [
                'left'    => Str::before($delimiter['left'], ']').':0]',
                'right'   => Str::before($delimiter['right'], ']').':0]',
                'display' => $delimiter['display'],
                'ascii'   => $delimiter['ascii'],
            ];

            if ($delimiter['ascii'] === true) {
                $explicitAsciiDelimiters[] = $item;
            } else {
                $explicitLatexDelimiters[] = $item;
            }
        }

        $explicitDelimiters = $allowAsciiMath
            ? \array_merge($explicitAsciiDelimiters, $explicitLatexDelimiters)
            : $explicitLatexDelimiters;

        $fields = [
            Schema\Bool::make('mathren.allow_asciimath')
                ->get(fn () => $allowAsciiMath),
            Schema\Arr::make('mathren.katex_options')
                ->get(fn () => $this->util->getKatexOptions()),
            Schema\Bool::make('mathren.enable_editor_buttons')
                ->get(fn () => \boolval($this->util->get('enable_editor_buttons'))),
            Schema\Bool::make('mathren.aliases_as_primary')
                ->get(fn () => \boolval($this->util->get('aliases_as_primary'))),
            Schema\Bool::make('mathren.enable_copy_tex')
                ->get(fn () => \boolval($this->util->get('enable_copy_tex'))),

            Schema\Arr::make('mathren.bbcode_delimiters')
                ->get(fn () => $bbCodeDelimiters),
            Schema\Arr::make('mathren.alias_delimiters')
                ->get(fn () => $aliasDelimiters),
            Schema\Arr::make('mathren.explicit_bbcode_delimiters')
                ->get(fn () => $explicitDelimiters),

            Schema\Arr::make('mathren.primary_block_delimiter')
                ->nullable()
                ->get(fn () => Arr::first($explicitLatexDelimiters, fn ($val) => $val['display'] === true)),
            Schema\Arr::make('mathren.primary_inline_delimiter')
                ->nullable()
                ->get(fn () => Arr::first($explicitLatexDelimiters, fn ($val) => $val['display'] === false)),
            Schema\Arr::make('mathren.primary_block_delimiter_alias')
                ->nullable()
                ->get(fn () => Arr::first($aliasLatexDelimiters, fn ($val) => $val['display'] === true)),
            Schema\Arr::make('mathren.primary_inline_delimiter_alias')
                ->nullable()
                ->get(fn () => Arr::first($aliasLatexDelimiters, fn ($val) => $val['display'] === false)),
        ];

        if ($allowAsciiMath) {
            $fields = \array_merge($fields, [
                Schema\Arr::make('mathren.primary_block_asciimath_delimiter')
                    ->nullable()
                    ->get(fn () => Arr::first($explicitAsciiDelimiters, fn ($val) => $val['display'] === true)),
                Schema\Arr::make('mathren.primary_inline_asciimath_delimiter')
                    ->nullable()
                    ->get(fn () => Arr::first($explicitAsciiDelimiters, fn ($val) => $val['display'] === false)),
                Schema\Arr::make('mathren.primary_block_asciimath_delimiter_alias')
                    ->nullable()
                    ->get(fn () => Arr::first($aliasAsciiDelimiters, fn ($val) => $val['display'] === true)),
                Schema\Arr::make('mathren.primary_inline_asciimath_delimiter_alias')
                    ->nullable()
                    ->get(fn () => Arr::first($aliasAsciiDelimiters, fn ($val) => $val['display'] === false)),
            ]);
        }

        return $fields;
    }
}