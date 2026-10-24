/*
 * This file is part of MathRen.
 *
 * Original Copyright Stichting Flarum (Flarum Foundation), Toby Zerner (toby.zerner@gmail.com).
 * Licensed under the MIT License. See license text at https://github.com/flarum/mentions/blob/master/LICENSE.
 */

import app from 'flarum/forum/app';
import { extend } from 'flarum/common/extend';
import CommentPost from 'flarum/forum/components/CommentPost';
import PostQuoteButton from 'ext:flarum/mentions/forum/fragments/PostQuoteButton';

import copyDelimiters from './utils/katex/copyDelimiters';
import getPrimaryDelimiters from './utils/katex/getPrimaryDelimiters';
import selectedText from './utils/mentions/selectedText';

export default function addPostQuoteButton() {
  extend(CommentPost.prototype, 'oncreate', function () {
    if (!('flarum-mentions' in flarum.extensions) || !app.forum.attribute('mathren.enable_copy_tex') || !PostQuoteButton) return;

    const post = this.attrs.post;
    const delimiters = getPrimaryDelimiters(app.forum);

    if (post.isHidden() || (app.session.user && !post.discussion().canReply())) return;

    const $postBody = this.$('.Post-body');
    const $container = $('<div class="MathRen-quoteButtonContainer"></div>');
    const button = new PostQuoteButton(post);

    const handler = function (e) {
      setTimeout(() => {
        const content = selectedText($postBody, copyDelimiters(delimiters));
        if (content) {
          button.content = content;
          m.render($container[0], button.render());

          const rects = window.getSelection().getRangeAt(0).getClientRects();
          const firstRect = rects[0];

          if (e.clientY < firstRect.bottom && e.clientX - firstRect.right < firstRect.left - e.clientX) {
            button.showStart(firstRect.left, firstRect.top);
          } else {
            const lastRect = rects[rects.length - 1];
            button.showEnd(lastRect.right, lastRect.bottom);
          }
        }
      }, 1);
    };

    this.$().after($container).on('mouseup', handler);

    if ('ontouchstart' in window) {
      document.addEventListener('selectionchange', handler, false);
    }
  });
}