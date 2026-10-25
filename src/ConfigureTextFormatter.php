<?php

declare(strict_types=1);

/*
 * This file is part of MathRen.
 *
 * For detailed copyright and license information, please view the
 * LICENSE file that was distributed with this source code.
 */

namespace TheTurk\MathRen;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use s9e\TextFormatter\Configurator;
use TheTurk\MathRen\Helpers\Util;

class ConfigureTextFormatter
{
    public function __construct(
        protected Util $util
    ) {
    }

    /**
     * Configure s9e/TextFormatter.
     */
    public function __invoke(Configurator $config): void
    {
        $katexOptions = $this->util->getKatexOptions();
        $bbDelimiters = $this->util->getForumDelimiters('bbcode');
        $allowAsciiMath = $this->util->getBool('allow_asciimath');
        $classes = $this->util->getClasses();

        foreach ($bbDelimiters as $delimiter) {
            $left = $delimiter['left'] ?? '';
            $right = $delimiter['right'] ?? '';
            $delimiterText = \trim(Str::after(Str::before($left, ']'), '['));

            if ($delimiterText === '' || $right === '') {
                continue;
            }

            $displayMode = ($delimiter['display'] ?? false) === true;
            $className = $displayMode ? 'block' : 'inline';

            $options = \json_encode(Arr::add($katexOptions, 'displayMode', $displayMode));

            if ($allowAsciiMath && (($delimiter['ascii'] ?? false) === true)) {
                $config->BBCodes->addCustom(
                    $left.'{TEXT}'.$right,
                    '<span>
                        <xsl:attribute name="class">'.$classes[$className].'</xsl:attribute>
                        <xsl:attribute name="data-s9e-livepreview-onupdate">if(typeof katex!==\'undefined\')katex.render((typeof ascii2tex!==\'undefined\') ? ascii2tex.parse(this.innerText) : this.innerText, this, '.$options.')</xsl:attribute>
                        <xsl:apply-templates/>
                        <script defer="" crossorigin="anonymous">
                            <xsl:attribute name="data-s9e-livepreview-onrender">if(typeof ascii2tex!==\'undefined\')this.parentNode.removeChild(this)</xsl:attribute>
                            <xsl:attribute name="integrity">'.$this->util->get('sri_asciimath2tex').'</xsl:attribute>
                            <xsl:attribute name="onload">window.ascii2tex=new AsciiMathParser()</xsl:attribute>
                            <xsl:attribute name="src">'.$this->util->get('cdn_asciimath2tex').'</xsl:attribute>
                        </script>
                        <script defer="" crossorigin="anonymous">
                            <xsl:attribute name="data-s9e-livepreview-onrender">if(typeof katex!==\'undefined\')this.parentNode.removeChild(this)</xsl:attribute>
                            <xsl:attribute name="integrity">'.$this->util->get('sri_katex').'</xsl:attribute>
                            <xsl:attribute name="onload">katex.render((typeof ascii2tex!==\'undefined\') ? ascii2tex.parse(this.parentNode.innerText) : this.parentNode.innerText, this.parentNode, '.$options.')</xsl:attribute>
                            <xsl:attribute name="src">'.$this->util->get('cdn_katex').'</xsl:attribute>
                        </script>
                    </span>'
                );
            } else {
                $config->BBCodes->addCustom(
                    $left.'{TEXT}'.$right,
                    '<span>
                        <xsl:attribute name="class">'.$classes[$className].'</xsl:attribute>
                        <xsl:attribute name="data-s9e-livepreview-onupdate">if(typeof katex!==\'undefined\')katex.render(this.innerText, this, '.$options.')</xsl:attribute>
                        <xsl:apply-templates/>
                        <script defer="" crossorigin="anonymous">
                            <xsl:attribute name="data-s9e-livepreview-onrender">if(typeof katex!==\'undefined\')this.parentNode.removeChild(this)</xsl:attribute>
                            <xsl:attribute name="integrity">'.$this->util->get('sri_katex').'</xsl:attribute>
                            <xsl:attribute name="onload">katex.render(this.parentNode.innerText, this.parentNode, '.$options.')</xsl:attribute>
                            <xsl:attribute name="src">'.$this->util->get('cdn_katex').'</xsl:attribute>
                        </script>
                    </span>'
                );
            }

            if (isset($config->tags[$delimiterText])) {
                $tag = $config->tags[$delimiterText];
                $tag->rules->ignoreTags();
                $tag->rules->disableAutoLineBreaks();
            }
        }
    }
}