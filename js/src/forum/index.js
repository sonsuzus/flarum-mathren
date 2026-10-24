/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';
import Page from 'flarum/common/components/Page';
import TextEditor from 'flarum/common/components/TextEditor';

import addCopyListener from './addCopyListener';
import addPostQuoteButton from './addPostQuoteButton';
import addTextEditorButton from './addTextEditorButton';
import delimiterReplacer from './utils/katex/delimiterReplacer';
import getPrimaryDelimiters from './utils/katex/getPrimaryDelimiters';
import getFlarumComposers from './utils/getFlarumComposers';

app.initializers.add(
  'the-turk-mathren',
  () => {
    const replaceDelimiters = (text = '', reverse = false, returnAsText = true) => {
      const span = document.createElement('span');
      span.textContent = text;

      return delimiterReplacer(span, delimiterReplacerOptions(reverse), returnAsText);
    };

    const preserveAliasesInCodeTag = (element) => {
      if (!app.forum.attribute('mathren.aliases_as_primary')) return;

      const codeNodeList = element.querySelectorAll('code');

      codeNodeList.forEach((c) => {
        if (c.classList.contains('hljs')) return;

        c.textContent = replaceDelimiters(c.textContent, true);
      });
    };

    const delimiterReplacerOptions = (reverse = false) => {
      const primaryDelimiters = getPrimaryDelimiters(app.forum, !reverse);
      const bbDelimiters = app.forum.attribute('mathren.bbcode_delimiters');
      const explicitBBDelimiters = app.forum.attribute('mathren.explicit_bbcode_delimiters');
      const aliasDelimiters = app.forum.attribute('mathren.alias_delimiters');
      const splitAtDelimiters = reverse ? bbDelimiters.concat(explicitBBDelimiters) : aliasDelimiters;

      let options = {
        primaryBlockDelimiter: primaryDelimiters.block,
        primaryInlineDelimiter: primaryDelimiters.inline,
        splitAtDelimiters,
      };

      if (app.forum.attribute('mathren.allow_asciimath')) {
        options['primaryBlockAsciiMathDelimiter'] = primaryDelimiters.block_asciimath;
        options['primaryInlineAsciiMathDelimiter'] = primaryDelimiters.inline_asciimath;
      }

      return options;
    };

    addPostQuoteButton();
    addTextEditorButton();
    addCopyListener();

    extend(Page.prototype, ['oncreate', 'onupdate'], () => preserveAliasesInCodeTag(document));

    if (window.s9e && window.s9e.TextFormatter) {
      override(window.s9e.TextFormatter, 'preview', function (original, preview, element) {
        original(replaceDelimiters(preview), element);
        preserveAliasesInCodeTag(element);
      });
    }

    getFlarumComposers().forEach((composer) => {
      if (!composer) return; // Eksik Flarum 2.0 bileşenlerine karşı koruma
      
      override(composer.prototype, 'onsubmit', function (original) {
        if (this.composer && this.composer.fields) {
          this.composer.fields.content(replaceDelimiters(this.composer.fields.content()));
        }
        return original();
      });
    });

    extend(TextEditor.prototype, 'oncreate', function (original, vnode) {
      const $textarea = vnode.dom.querySelector('textarea');
      if (!$textarea || !app.composer || !app.composer.body) return;

      const composerClass = app.composer.body.componentClass || app.composer.body.constructor;

      if (getFlarumComposers().indexOf(composerClass) === -1) {
        $textarea.addEventListener('input', function () {
          $textarea.value = replaceDelimiters($textarea.value);
        });
      } else {
        if (!app.forum.attribute('mathren.aliases_as_primary')) return;
        $textarea.value = replaceDelimiters(this.attrs.value || this.value || '', true);
      }
    });
  },
  -500 // since we're overriding things...
);