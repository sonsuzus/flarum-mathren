<?php

declare(strict_types=1);

/*
 * This file is part of MathRen.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TheTurk\MathRen;

use Flarum\Api\Schema;
use TheTurk\MathRen\Helpers\Util;

/**
 * Serializes MathRen settings and parsed delimiter structures into `ForumResource`
 * using the `mathren.*` attribute names expected by the forum frontend.
 */
class LoadSettings
{
    public function __construct(
        protected Util $util
    ) {
    }

    /**
     * @return list<Schema\Attribute>
     */
    public function __invoke(): array
    {
        return [
            Schema\Boolean::make('mathren.enable_editor_buttons')
                ->get(fn (): bool => $this->util->getBool('enable_editor_buttons')),

            Schema\Boolean::make('mathren.allow_asciimath')
                ->get(fn (): bool => $this->util->getBool('allow_asciimath')),

            Schema\Boolean::make('mathren.aliases_as_primary')
                ->get(fn (): bool => $this->util->getBool('aliases_as_primary')),

            Schema\Boolean::make('mathren.enable_copy_tex')
                ->get(fn (): bool => $this->util->getBool('enable_copy_tex')),

            Schema\Arr::make('mathren.bbcode_delimiters')
                ->get(fn (): array => $this->util->getForumDelimiters('bbcode')),

            Schema\Arr::make('mathren.explicit_bbcode_delimiters')
                ->get(fn (): array => []),

            Schema\Arr::make('mathren.alias_delimiters')
                ->get(fn (): array => $this->util->getForumDelimiters('alias')),

            Schema\Arr::make('mathren.primary_block_delimiter')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('block')),

            Schema\Arr::make('mathren.primary_inline_delimiter')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('inline')),

            Schema\Arr::make('mathren.primary_block_delimiter_alias')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('aliasBlock')),

            Schema\Arr::make('mathren.primary_inline_delimiter_alias')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('aliasInline')),

            Schema\Arr::make('mathren.primary_block_asciimath_delimiter')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('blockAscii')),

            Schema\Arr::make('mathren.primary_inline_asciimath_delimiter')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('inlineAscii')),

            Schema\Arr::make('mathren.primary_block_asciimath_delimiter_alias')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('aliasBlockAscii')),

            Schema\Arr::make('mathren.primary_inline_asciimath_delimiter_alias')
                ->get(fn (): array => $this->util->getPrimaryDelimiter('aliasInlineAscii')),
        ];
    }
}