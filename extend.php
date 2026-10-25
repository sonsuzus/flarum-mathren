<?php

declare(strict_types=1);

/*
 * This file is part of MathRen.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TheTurk\MathRen;

use Flarum\Api\Resource\ForumResource;
use Flarum\Extend;
use TheTurk\MathRen\Helpers\Util;

$settings = (new Extend\Settings())
    ->registerLessConfigVar(
        'config-copy-tex',
        Util::PREFIX.'enable_copy_tex',
        static fn (mixed $setting): string => \filter_var($setting, \FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false'
    );

foreach (Util::DEFAULTS as $key => $default) {
    $settings->default(Util::PREFIX.$key, $default);
}

return [
    (new Extend\Frontend('forum'))
        ->css(__DIR__.'/less/forum.less')
        ->js(__DIR__.'/js/dist/forum.js'),

    (new Extend\Frontend('admin'))
        ->css(__DIR__.'/less/admin.less')
        ->js(__DIR__.'/js/dist/admin.js'),

    new Extend\Locales(__DIR__.'/locale'),

    (new Extend\ApiResource(ForumResource::class))
        ->fields(LoadSettings::class),

    (new Extend\Formatter())
        ->configure(ConfigureTextFormatter::class),

    $settings,
];