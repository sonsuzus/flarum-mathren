/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import { extend, override } from 'flarum/common/extend';
import app from 'flarum/forum/app';

import addCopyListener from './addCopyListener';
import addPostQuoteButton from './addPostQuoteButton';
import addTextEditorButton from './addTextEditorButton';
import delimiterReplacer from './utils/katex/delimiterReplacer';
import getPrimaryDelimiters from './utils/katex/getPrimaryDelimiters';
import getFlarumComposers from './utils/getFlarumComposers';

const toArray = (value) => (Array.isArray(value) ? value : []);

app.initializers.add(
  'the-turk-mathren',
  () => {
    const replaceDelimiters = (text = '', reverse = false, returnAsText = true) => {
      const span = document.createElement('span');
      span.textContent = text || '';

      return delimiterReplacer(span, delimiterReplacerOptions(reverse), returnAsText);
    };

    const preserveAliasesInCodeTag = (element) => {
      if (!element || !app.forum?.attribute('mathren.aliases_as_primary')) return;

      const codeNodeList = element.querySelectorAll('code');

      codeNodeList.forEach((c) => {
        if (c.classList.contains('hljs')) return;

        c.textContent = replaceDelimiters(c.textContent, true);
      });
    };

    const delimiterReplacerOptions = (reverse = false) => {
      const primaryDelimiters = getPrimaryDelimiters(app.forum, !reverse) || {};

      const bbDelimiters = toArray(app.forum?.attribute('mathren.bbcode_delimiters') || []);
      const explicitBBDelimiters = toArray(app.forum?.attribute('mathren.explicit_bbcode_delimiters') || []);
      const aliasDelimiters = toArray(app.forum?.attribute('mathren.alias_delimiters') || []);

      const splitAtDelimiters = reverse ? bbDelimiters.concat(explicitBBDelimiters) : aliasDelimiters;

      const options = {
        primaryBlockDelimiter: primaryDelimiters.block,
        primaryInlineDelimiter: primaryDelimiters.inline,
        splitAtDelimiters: toArray(splitAtDelimiters),
      };

      if (app.forum?.attribute('mathren.allow_asciimath')) {
        options.primaryBlockAsciiMathDelimiter = primaryDelimiters.block_asciimath;
        options.primaryInlineAsciiMathDelimiter = primaryDelimiters.inline_asciimath;
      }

      return options;
    };

    addPostQuoteButton();
    addTextEditorButton();
    addCopyListener();

    extend('flarum/common/components/Page', ['oncreate', 'onupdate'], () => preserveAliasesInCodeTag(document));

    if (window.s9e?.TextFormatter) {
      override(window.s9e.TextFormatter, 'preview', function (original, preview, element) {
        original(replaceDelimiters(preview), element);
        preserveAliasesInCodeTag(element);
      });
    }

    // Override onsubmit across all native Flarum composers (DiscussionComposer,
    // EditPostComposer, ReplyComposer) via Flarum 2.0 string module paths so
    // lazy-loaded composer chunks are hooked safely when loaded.
    getFlarumComposers().forEach((composerPath) => {
      if (!composerPath) return;

      override(composerPath, 'onsubmit', function (original) {
        if (this.composer?.fields && typeof this.composer.fields.content === 'function') {
          this.composer.fields.content(replaceDelimiters(this.composer.fields.content()));
        }

        return original();
      });
    });

    extend('flarum/common/components/TextEditor', 'oncreate', function (_, vnode) {
      const $textarea = vnode?.dom?.querySelector('textarea');
      if (!$textarea || !app.composer?.body) return;

      const composerClass = app.composer.body.componentClass || app.composer.body.constructor;
      const loadedComposers = getFlarumComposers(true);
      const isNativeComposer = loadedComposers.some(
        (Composer) => composerClass === Composer || (typeof Composer === 'function' && composerClass?.prototype instanceof Composer)
      );

      if (!isNativeComposer) {
        $textarea.addEventListener('input', function () {
          $textarea.value = replaceDelimiters($textarea.value);
        });
      } else {
        if (!app.forum?.attribute('mathren.aliases_as_primary')) return;
        const initialValue = this.attrs?.value || this.value || $textarea.value || '';
        const replacedValue = replaceDelimiters(initialValue, true);
        $textarea.value = replacedValue;
        if (typeof this.attrs?.onchange === 'function') {
          this.attrs.onchange(replacedValue);
        }
      }
    });
  },
  -500 // since we're overriding things...
);