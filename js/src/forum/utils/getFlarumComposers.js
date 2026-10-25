/*
 * This file is part of MathRen.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

const FLARUM_COMPOSER_MODULES = [
  'flarum/forum/components/DiscussionComposer',
  'flarum/forum/components/EditPostComposer',
  'flarum/forum/components/ReplyComposer',
];

/**
 * Returns a list of native Flarum composer components.
 *
 * In Flarum 2.0, composer components (including `EditPostComposer`,
 * `DiscussionComposer`, and `ReplyComposer`) are lazy-loaded chunks.
 *
 * - When `resolveClasses` is `false` (default), returns module path strings
 *   for safe use with Flarum 2.0's `extend()` / `override()` utilities without
 *   accessing `.prototype` on unloaded modules.
 * - When `resolveClasses` is `true`, safely queries `flarum.reg` at runtime
 *   and returns the currently loaded composer component classes.
 *
 * @param {boolean} resolveClasses
 * @return {Array<string|Function>}
 */
export default function getFlarumComposers(resolveClasses = false) {
  if (!resolveClasses) {
    return [...FLARUM_COMPOSER_MODULES];
  }

  if (typeof flarum === 'undefined' || !flarum.reg) {
    return [];
  }

  return FLARUM_COMPOSER_MODULES.map((modulePath) => {
    const registryPath = modulePath.replace(/^flarum\//, '');

    try {
      return flarum.reg.get('core', registryPath);
    } catch (e) {
      // Lazy-loaded composer chunk has not been requested yet; ignore safely.
      return null;
    }
  }).filter(Boolean);
}