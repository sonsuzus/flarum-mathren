/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import app from 'flarum/forum/app';
import Button from 'flarum/common/components/Button';
import Component from 'flarum/common/Component';
import Dropdown from 'flarum/common/components/Dropdown';
import Separator from 'flarum/common/components/Separator';
import ItemList from 'flarum/common/utils/ItemList';
import Icon from 'flarum/common/components/Icon';

import getPrimaryDelimiters from '../utils/katex/getPrimaryDelimiters';
import getFlarumComposers from '../utils/getFlarumComposers';

export default class TextEditorButton extends Component {
  oninit(vnode) {
    super.oninit(vnode);
    this.isAsciiMath = false;
  }

  oncreate(vnode) {
    super.oncreate(vnode);

    $(vnode.dom)
      .find('label.checkbox')
      .on('click', function (e) {
        e.stopPropagation();
      });
  }

  view() {
    return Dropdown.component(
      {
        className: 'MathRen-buttonsDropdown',
        buttonClassName: 'Button Button--flat',
        label: <Icon name="fas fa-square-root-alt" />,
      },
      this.items().toArray()
    );
  }

  items() {
    const items = new ItemList();

    items.add(
      'mathren-blockButton',
      Button.component(
        {
          icon: 'fas fa-vector-square',
          onclick: () => this.wrapSelection(true),
        },
        app.translator.trans('the-turk-mathren.forum.composer.block_expression' + (this.isAsciiMath ? '_asciimath' : ''))
      ),
      50
    );

    items.add(
      'mathren-inlineButton',
      Button.component(
        {
          icon: 'fas fa-grip-lines',
          onclick: () => this.wrapSelection(),
        },
        app.translator.trans('the-turk-mathren.forum.composer.inline_expression' + (this.isAsciiMath ? '_asciimath' : ''))
      ),
      0
    );

    if (app.forum.attribute('mathren.allow_asciimath')) {
      items.add('mathren-separator', Separator.component());

      items.add(
        'mathren-asciiMath',
        <label className="checkbox">
          <input
            type="checkbox"
            onchange={(e) => {
              const target = e.target;
              this.isAsciiMath = target.checked;
              m.redraw.sync();
              e.redraw = false;
            }}
          />
          {app.translator.trans('the-turk-mathren.forum.composer.asciimath_only')}
        </label>
      );
    }

    return items;
  }

  wrapSelection(displayMode = false) {
    const composerClass = app.composer.body?.componentClass;

    this.delimiters = getPrimaryDelimiters(app.forum, getFlarumComposers().indexOf(composerClass) === -1);

    const leftDelim = displayMode
      ? this.isAsciiMath
        ? this.delimiters.block_asciimath['left']
        : this.delimiters.block['left']
      : this.isAsciiMath
      ? this.delimiters.inline_asciimath['left']
      : this.delimiters.inline['left'];

    const rightDelim = displayMode
      ? this.isAsciiMath
        ? this.delimiters.block_asciimath['right']
        : this.delimiters.block['right']
      : this.isAsciiMath
      ? this.delimiters.inline_asciimath['right']
      : this.delimiters.inline['right'];

    const selectionRange = this.attrs.textEditor.getSelectionRange();

    if (selectionRange[0] !== selectionRange[1]) {
      this.attrs.textEditor.insertAt(selectionRange[0], leftDelim);
      this.attrs.textEditor.insertAt(selectionRange[1] + leftDelim.length, rightDelim);
    } else {
      this.attrs.textEditor.replaceBeforeCursor(selectionRange[0], leftDelim + rightDelim);
      this.attrs.textEditor.moveCursorTo(selectionRange[0] + leftDelim.length);
    }
  }
}