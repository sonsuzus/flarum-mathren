/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * Get the primary delimiters that'll be used by text editor buttons,
 * copy-tex plugin, `EditPostComposer` etc.
 *
 * @param forum   Must be equal to `app.forum`. Used for retrieving settings.
 * @param reverse To return the primary BBCode delimiters no matter what.
 *
 * @return { Object }
 */
export default function getPrimaryDelimiters(forum, reverse = false) {
  let delimiters = {};

  // Hata durumlarına karşı varsayılan (fallback) değerler
  const fallbackAliasInline = { left: '\\(', right: '\\)', display: false, ascii: false };
  const fallbackAliasBlock = { left: '$$', right: '$$', display: true, ascii: false };
  const fallbackBBInline = { left: '[imath]', right: '[/imath]', display: false, ascii: false };
  const fallbackBBBlock = { left: '[math]', right: '[/math]', display: true, ascii: false };

  if (forum.attribute('mathren.aliases_as_primary') && reverse === false) {
    delimiters = {
      inline: forum.attribute('mathren.primary_inline_delimiter_alias') || fallbackAliasInline,
      block: forum.attribute('mathren.primary_block_delimiter_alias') || fallbackAliasBlock,
    };

    if (forum.attribute('mathren.allow_asciimath')) {
      delimiters['inline_asciimath'] = forum.attribute('mathren.primary_inline_asciimath_delimiter_alias') || { left: '\\{', right: '\\}', display: false, ascii: true };
      delimiters['block_asciimath'] = forum.attribute('mathren.primary_block_asciimath_delimiter_alias') || { left: '\\$', right: '\\$', display: true, ascii: true };
    }

    return delimiters;
  }

  delimiters = {
    inline: forum.attribute('mathren.primary_inline_delimiter') || fallbackBBInline,
    block: forum.attribute('mathren.primary_block_delimiter') || fallbackBBBlock,
  };

  if (forum.attribute('mathren.allow_asciimath')) {
    delimiters['inline_asciimath'] = forum.attribute('mathren.primary_inline_asciimath_delimiter') || { left: '[iasmath]', right: '[/iasmath]', display: false, ascii: true };
    delimiters['block_asciimath'] = forum.attribute('mathren.primary_block_asciimath_delimiter') || { left: '[asmath]', right: '[/asmath]', display: true, ascii: true };
  }

  return delimiters;
}