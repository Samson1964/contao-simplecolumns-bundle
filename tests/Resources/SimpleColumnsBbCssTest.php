<?php

declare(strict_types=1);

/*
 * Dieses Bundle ordnet Inhaltselemente in Contao 4.13 und Contao 5 in Spalten an.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSimpleColumnsBundle\Tests\Resources;

use PHPUnit\Framework\TestCase;

/**
 * Prüft, dass die border-box-Variante des Stylesheets allen Spalten einer Zeile
 * die gleiche Innenbreite gibt.
 *
 * Praxisfall (schachbund.de): Bilder und Videos in Spalten wurden unterschiedlich
 * groß angezeigt. Ursache war ein asymmetrischer Innenabstand: `.sc` setzte
 * `padding-left: 15px`, `.sc-first` überschrieb das auf 0. Unter `box-sizing:
 * border-box` zählt Padding zur deklarierten Breite - die erste Spalte einer
 * Zeile hatte dadurch 15px mehr nutzbare Innenbreite als alle folgenden. Ein
 * echtes Rendering im Browser bestätigte den Unterschied exakt (328px vs.
 * 313px). Die content-box-Variante (simple_columns.css) ist nicht betroffen, weil
 * sie den Zwischenraum über Rand statt Innenabstand herstellt - Rand zählt bei
 * content-box nicht zur deklarierten Breite.
 */
class SimpleColumnsBbCssTest extends TestCase
{
    private const PATH = __DIR__.'/../../src/Resources/public/styles/simple_columns_bb.css';

    public function testScFirstUeberschreibtDenLinkenInnenabstandNichtMehr(): void
    {
        $strCss = file_get_contents(self::PATH);
        $this->assertNotFalse($strCss, 'Stylesheet nicht lesbar: '.self::PATH);

        $this->assertMatchesRegularExpression('~\.sc-first\s*\{~', $strCss, 'Regel .sc-first fehlt komplett');

        preg_match('~\.sc-first\s*\{([^}]*)\}~', $strCss, $match);
        $strBlock = $match[1] ?? '';

        $this->assertStringNotContainsString(
            'padding-left',
            $strBlock,
            '.sc-first überschreibt padding-left wieder - das macht die erste Spalte '
            .'jeder Zeile breiter als die übrigen (border-box zählt Padding zur Breite)',
        );
    }

    /**
     * Alle Spalten-Klassen (sc2 bis sc10, auch die -2/-3/... Breitenvarianten)
     * müssen dieselbe .sc-first-Regel benutzen; es darf keine spaltenzahl-
     * spezifische Ausnahme mit eigenem padding-left geben.
     */
    public function testKeineSpaltenspezifischeAusnahmeFuerPaddingLeft(): void
    {
        $strCss = file_get_contents(self::PATH);
        $this->assertNotFalse($strCss);

        preg_match_all('~\.sc\d+(?:-\d+)?-first\s*\{([^}]*)\}~', $strCss, $matches);

        foreach ($matches[1] as $strBlock) {
            $this->assertStringNotContainsString('padding-left', $strBlock);
        }
    }
}
