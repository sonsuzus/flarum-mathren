/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

const FALLBACK_DELIMITERS = {
  aliasInline: { left: '\\(', right: '\\)', display: false, ascii: false },
  aliasBlock: { left: '$$', right: '$$', display: true, ascii: false },
  aliasInlineAscii: { left: '\\{', right: '\\}', display: false, ascii: true },
  aliasBlockAscii: { left: '\\$', right: '\\$', display: true, ascii: true },
  bbInline: { left: '[imath]', right: '[/imath]', display: false, ascii: false },
  bbBlock: { left: '[math]', right: '[/math]', display: true, ascii: false },
  bbInlineAscii: { left: '[iasmath]', right: '[/iasmath]', display: false, ascii: true },
  bbBlockAscii: { left: '[asmath]', right: '[/asmath]', display: true, ascii: true },
};

function ensureDelimiter(value, fallback) {
  if (value && typeof value === 'object' && typeof value.left === 'string' && typeof value.right === 'string') {
    return value;
  }

  return { ...fallback };
}

/**
 * Get the primary delimiters that'll be used by text editor buttons,
 * copy-tex plugin, `EditPostComposer` etc.
 *
 * @param {Object} forum   Must be equal to `app.forum`. Used for retrieving settings.
 * @param {boolean} reverse To return the primary BBCode delimiters no matter what.
 *
 * @return {Object}
 */
export default function getPrimaryDelimiters(forum, reverse = false) {
  const getAttr = (key) => (forum && typeof forum.attribute === 'function' ? forum.attribute(key) : undefined);

  if (getAttr('mathren.aliases_as_primary') && reverse === false) {
    const delimiters = {
      inline: ensureDelimiter(getAttr('mathren.primary_inline_delimiter_alias'), FALLBACK_DELIMITERS.aliasInline),
      block: ensureDelimiter(getAttr('mathren.primary_block_delimiter_alias'), FALLBACK_DELIMITERS.aliasBlock),
      inline_asciimath: ensureDelimiter(getAttr('mathren.primary_inline_asciimath_delimiter_alias'), FALLBACK_DELIMITERS.aliasInlineAscii),
      block_asciimath: ensureDelimiter(getAttr('mathren.primary_block_asciimath_delimiter_alias'), FALLBACK_DELIMITERS.aliasBlockAscii),
    };

    return delimiters;
  }

  return {
    inline: ensureDelimiter(getAttr('mathren.primary_inline_delimiter'), FALLBACK_DELIMITERS.bbInline),
    block: ensureDelimiter(getAttr('mathren.primary_block_delimiter'), FALLBACK_DELIMITERS.bbBlock),
    inline_asciimath: ensureDelimiter(getAttr('mathren.primary_inline_asciimath_delimiter'), FALLBACK_DELIMITERS.bbInlineAscii),
    block_asciimath: ensureDelimiter(getAttr('mathren.primary_block_asciimath_delimiter'), FALLBACK_DELIMITERS.bbBlockAscii),
  };
}