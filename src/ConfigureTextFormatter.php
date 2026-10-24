<?php

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
     *
     * @param Configurator $config TextFormatter configurator.
     * @return void
     */
    public function __invoke(Configurator $config): void
    {
        $katexOptions = $this->util->getKatexOptions();
        $bbDelimiters = $this->util->getDelimitersWithOptions('bbcode');
        $allowAsciiMath = \boolval($this->util->get('allow_asciimath'));

        if ($allowAsciiMath) {
            $bbDelimiters = \array_merge(
                $bbDelimiters,
                $this->util->getDelimitersWithOptions('bbcode', true)
            );
        }

        $classes = $this->util->getClasses();

        foreach ($bbDelimiters as $delimiter) {
            $delimiterText = Str::after(Str::before($delimiter['left'], ']'), '[');
            $className = $delimiter['display'] === true ? 'block' : 'inline';
            $displayMode = $delimiter['display'] === true;

            $options = \json_encode(Arr::add($katexOptions, 'displayMode', $displayMode));

            if ($allowAsciiMath && ($delimiter['ascii'] === true)) {
                $config->BBCodes->addCustom(
                    $delimiter['left'].'{TEXT}'.$delimiter['right'],
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
                    $delimiter['left'].'{TEXT}'.$delimiter['right'],
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

            $tag = $config->tags[$delimiterText];
            $tag->rules->ignoreTags();
            $tag->rules->disableAutoLineBreaks();
        }
    }
}