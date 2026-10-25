/*
 * This file is part of MathRen.
 *
 * Original Copyright Khan Academy. Licensed under the MIT License.
 * See license text at https://github.com/KaTeX/KaTeX/blob/master/LICENSE.
 */

const findEndOfMath = function (delimiter, text, startIndex) {
  let index = startIndex;
  let braceLevel = 0;
  const delimLength = delimiter.length;

  while (index < text.length) {
    const character = text[index];

    if (braceLevel <= 0 && text.slice(index, index + delimLength) === delimiter) {
      return index;
    } else if (character === '\\') {
      index++;
    } else if (character === '{') {
      braceLevel++;
    } else if (character === '}') {
      braceLevel--;
    }

    index++;
  }

  return -1;
};

const escapeRegex = function (string) {
  return String(string).replace(/[-/\\^$*+?.()|[\]{}]/g, '\\$&');
};

const amsRegex = /^\\begin{/;

export default function splitAtDelimiters(text, delimiters = []) {
  if (typeof text !== 'string' || text === '') {
    return [{ type: 'text', data: text || '' }];
  }

  const safeDelimiters = (Array.isArray(delimiters) ? delimiters : []).filter(
    (d) => d && typeof d.left === 'string' && d.left.length > 0 && typeof d.right === 'string' && d.right.length > 0
  );

  if (safeDelimiters.length === 0) {
    return [{ type: 'text', data: text }];
  }

  let index;
  const data = [];
  const regexLeft = new RegExp('(' + safeDelimiters.map((x) => escapeRegex(x.left)).join('|') + ')');

  while (true) {
    index = text.search(regexLeft);
    if (index === -1) {
      break;
    }
    if (index > 0) {
      data.push({
        type: 'text',
        data: text.slice(0, index),
      });
      text = text.slice(index);
    }

    const i = safeDelimiters.findIndex((delim) => text.startsWith(delim.left));
    if (i === -1) {
      break;
    }

    index = findEndOfMath(safeDelimiters[i].right, text, safeDelimiters[i].left.length);
    if (index === -1) {
      break;
    }

    const rawData = text.slice(0, index + safeDelimiters[i].right.length);
    const math = amsRegex.test(rawData) ? rawData : text.slice(safeDelimiters[i].left.length, index);

    data.push({
      type: 'math',
      data: math,
      rawData,
      display: safeDelimiters[i].display,
      ascii: safeDelimiters[i].ascii,
    });

    text = text.slice(index + safeDelimiters[i].right.length);
  }

  if (text !== '') {
    data.push({
      type: 'text',
      data: text,
    });
  }

  return data;
}