/*
 * This file is part of MathRen.
 *
 * Original Copyright Khan Academy. Licensed under the MIT License.
 * See license text at https://github.com/KaTeX/KaTeX/blob/master/LICENSE.
 */

// Replace .katex elements with their TeX source (<annotation> element).
// Modifies fragment in-place. Useful for writing your own 'copy' handler,
// as in copy-tex.js.
export default function katexReplaceWithTex(fragment, copyDelimiters = {}) {
  const inlineDelims = Array.isArray(copyDelimiters?.inline) && copyDelimiters.inline.length >= 2 ? copyDelimiters.inline : ['[imath]', '[/imath]'];
  const displayDelims = Array.isArray(copyDelimiters?.display) && copyDelimiters.display.length >= 2 ? copyDelimiters.display : ['[math]', '[/math]'];

  // Remove .katex-html blocks that are preceded by .katex-mathml blocks
  // (which will get replaced below).
  const katexHtml = fragment.querySelectorAll('.katex-mathml + .katex-html');
  for (let i = 0; i < katexHtml.length; i++) {
    const element = katexHtml[i];
    if (element.remove) {
      element.remove();
    } else if (element.parentNode) {
      element.parentNode.removeChild(element);
    }
  }

  // Replace .katex-mathml elements with their annotation (TeX source)
  // descendant, with inline delimiters.
  const katexMathml = fragment.querySelectorAll('.katex-mathml');
  for (let i = 0; i < katexMathml.length; i++) {
    const element = katexMathml[i];
    const texSource = element.querySelector('annotation');
    if (texSource) {
      if (element.replaceWith) {
        element.replaceWith(texSource);
      } else if (element.parentNode) {
        element.parentNode.replaceChild(texSource, element);
      }
      texSource.innerHTML = inlineDelims[0] + texSource.innerHTML + inlineDelims[1];
    }
  }

  // Switch display math to display delimiters.
  const displays = fragment.querySelectorAll('.katex-display annotation');
  for (let i = 0; i < displays.length; i++) {
    const element = displays[i];
    element.innerHTML =
      displayDelims[0] +
      element.innerHTML.slice(inlineDelims[0].length, element.innerHTML.length - inlineDelims[1].length) +
      displayDelims[1];
  }

  return fragment;
}
