/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Define delimiters that will be used in `copy-tex` plugin
// see https://github.com/KaTeX/KaTeX/blob/master/contrib/copy-tex/katex2tex.js#L1-L5
export default function copyDelimiters(delimiters = {}) {
  const inlineLeft = delimiters?.inline?.left ?? '[imath]';
  const inlineRight = delimiters?.inline?.right ?? '[/imath]';
  const blockLeft = delimiters?.block?.left ?? '[math]';
  const blockRight = delimiters?.block?.right ?? '[/math]';

  return {
    inline: [inlineLeft, inlineRight],
    display: [blockLeft, blockRight],
  };
}
