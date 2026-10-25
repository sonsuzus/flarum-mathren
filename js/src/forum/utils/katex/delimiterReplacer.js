/*
 * This file is part of MathRen.
 *
 * Original Copyright Khan Academy. Licensed under the MIT License.
 * See license text at https://github.com/KaTeX/KaTeX/blob/master/LICENSE.
 */

import splitAtDelimiters from './splitAtDelimiters';

const replaceDelimitersInText = function (text, options = {}) {
  const delimiters = Array.isArray(options?.splitAtDelimiters) ? options.splitAtDelimiters : [];
  const data = splitAtDelimiters(text, delimiters);

  if (data.length === 1 && data[0].type === 'text') {
    // There is no formula in the text.
    // Return null so the current text node is not unnecessarily replaced.
    return null;
  }

  const fragment = document.createDocumentFragment();

  for (let i = 0; i < data.length; i++) {
    if (data[i].type === 'text') {
      fragment.appendChild(document.createTextNode(data[i].data));
    } else {
      const span = document.createElement('span');
      const math = data[i].data;
      const displayMode = data[i].display === true;
      const asciiMath = data[i].ascii === true;
      const delimiter = displayMode
        ? asciiMath
          ? options.primaryBlockAsciiMathDelimiter
          : options.primaryBlockDelimiter
        : asciiMath
          ? options.primaryInlineAsciiMathDelimiter
          : options.primaryInlineDelimiter;

      if (delimiter && typeof delimiter.left === 'string' && typeof delimiter.right === 'string') {
        span.textContent = delimiter.left + math + delimiter.right;
      } else {
        span.textContent = data[i].rawData || math;
      }

      fragment.appendChild(span);
    }
  }

  return fragment;
};

/**
 * Matches math expressions and replaces their BBCode delimiters
 * `[math]...[/math]` with their aliases `($$...$$)` (or vice-versa).
 *
 * @param {Node} elem            The element to inspect for math expressions.
 * @param {Object} options       Contains primary replacer delimiter information.
 * @param {boolean} returnAsText Whether to return `elem.innerText` instead of `elem`.
 *
 * @return {string|Node}
 */
export default function delimiterReplacer(elem, options = {}, returnAsText = false) {
  if (!elem || !elem.childNodes) {
    return returnAsText === true ? '' : elem;
  }

  for (let i = 0; i < elem.childNodes.length; i++) {
    const childNode = elem.childNodes[i];

    if (childNode.nodeType === 3) {
      // Text node
      const frag = replaceDelimitersInText(childNode.textContent, options);

      if (frag) {
        i += frag.childNodes.length - 1;
        elem.replaceChild(frag, childNode);
      }
    } else if (childNode.nodeType === 1) {
      delimiterReplacer(childNode, options, false);
    }
  }

  return returnAsText === true ? elem.innerText : elem;
}
