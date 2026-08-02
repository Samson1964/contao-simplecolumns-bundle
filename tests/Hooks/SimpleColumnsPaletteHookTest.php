<?php

declare(strict_types=1);

/*
 * Dieses Bundle ordnet Inhaltselemente in Contao 4.13 und Contao 5 in Spalten an.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSimpleColumnsBundle\Tests\Hooks;

use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSimpleColumnsBundle\Hooks\SimpleColumnsPaletteHook;

/**
 * Prueft das Einhaengen der Spaltenfelder in die Paletten von tl_content.
 *
 * Der Hook ist die Stelle, an der frueher die Bundle-Ladereihenfolge
 * durchgeschlagen hat: Die Ergaenzung lag in dca/tl_content.php und erreichte nur
 * Bundles, deren Paketname alphabetisch vor diesem Bundle liegt.
 */
class SimpleColumnsPaletteHookTest extends TestCase
{
    /**
     * Setzt das globale DCA-Array vor jedem Test zurueck.
     *
     * Der Hook arbeitet ausschliesslich auf $GLOBALS['TL_DCA'], deshalb muss der
     * Zustand zwischen den Tests sauber getrennt werden.
     */
    protected function setUp(): void
    {
        parent::setUp();

        unset($GLOBALS['TL_DCA']);
    }

    /**
     * Raeumt das globale DCA-Array nach jedem Test wieder auf.
     */
    protected function tearDown(): void
    {
        unset($GLOBALS['TL_DCA']);

        parent::tearDown();
    }

    /**
     * Vor dem Experten-Bereich wird eingehaengt, wenn es ihn gibt.
     */
    public function testHaengtVorExpertLegendEin(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = '{type_legend},type;{protected_legend:hide},protected;{expert_legend:hide},guests,cssID';

        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_content');

        $this->assertSame(
            '{type_legend},type;{protected_legend:hide},protected;{simple_columns_legend},simple_columns,simple_columns_rowspan,simple_columns_close,simple_columns_wrapper,simple_columns_autoheight,simple_columns_border;{expert_legend:hide},guests,cssID',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['text'],
        );
    }

    /**
     * Ohne Experten-Bereich wird vor dem Schutz-Bereich eingehaengt.
     */
    public function testHaengtVorProtectedLegendEin(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['tweet'] = '{type_legend},type;{protected_legend:hide},protected';

        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_content');

        $this->assertStringContainsString(
            '{simple_columns_legend},simple_columns,',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['tweet'],
        );
        $this->assertStringEndsWith(
            'simple_columns_border;{protected_legend:hide},protected',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['tweet'],
        );
    }

    /**
     * Fehlen beide Bereiche, werden die Felder hinten angehaengt.
     */
    public function testHaengtOhnePassendeLegendeHintenAn(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['forum_thread_editor'] = '{type_legend},type,headline';

        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_content');

        $this->assertSame(
            '{type_legend},type,headline;{simple_columns_legend},simple_columns,simple_columns_rowspan,simple_columns_close,simple_columns_wrapper,simple_columns_autoheight,simple_columns_border',
            $GLOBALS['TL_DCA']['tl_content']['palettes']['forum_thread_editor'],
        );
    }

    /**
     * Alle Paletten werden erfasst, nicht nur die zuerst geladenen.
     *
     * Das ist der eigentliche Zweck des Hooks: Er laeuft, nachdem saemtliche
     * DCA-Dateien eingebunden wurden.
     */
    public function testErfasstAllePaletten(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes'] = [
            'chesstable' => '{type_legend},type;{expert_legend:hide},cssID',
            'tweet' => '{type_legend},type;{expert_legend:hide},cssID',
            'volunteeringlist' => '{type_legend},type;{expert_legend:hide},cssID',
        ];

        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_content');

        foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $strName => $strPalette) {
            $this->assertStringContainsString('simple_columns', $strPalette, 'Palette "'.$strName.'" wurde nicht ergaenzt');
        }
    }

    /**
     * __selector__ ist ein Array und darf nicht angefasst werden.
     */
    public function testLaesstSelectorArrayUnangetastet(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__'] = ['type', 'addImage'];
        $GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = '{type_legend},type';

        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_content');

        $this->assertSame(['type', 'addImage'], $GLOBALS['TL_DCA']['tl_content']['palettes']['__selector__']);
    }

    /**
     * Ein zweiter Aufruf darf die Felder nicht doppelt eintragen.
     *
     * Der Hook kann pro Request mehrfach anlaufen, etwa wenn das DCA fuer mehrere
     * Requests neu geladen wird.
     */
    public function testIstIdempotent(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = '{type_legend},type;{expert_legend:hide},cssID';

        $objHook = new SimpleColumnsPaletteHook();
        $objHook->onLoadDataContainer('tl_content');
        $strNachErstemAufruf = $GLOBALS['TL_DCA']['tl_content']['palettes']['text'];
        $objHook->onLoadDataContainer('tl_content');

        $this->assertSame($strNachErstemAufruf, $GLOBALS['TL_DCA']['tl_content']['palettes']['text']);
        $this->assertSame(1, substr_count($GLOBALS['TL_DCA']['tl_content']['palettes']['text'], 'simple_columns_legend'));
    }

    /**
     * Andere Tabellen bleiben unveraendert.
     */
    public function testIgnoriertAndereTabellen(): void
    {
        $GLOBALS['TL_DCA']['tl_content']['palettes']['text'] = '{type_legend},type';
        $GLOBALS['TL_DCA']['tl_module']['palettes']['navigation'] = '{title_legend},name,type';

        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_module');

        $this->assertSame('{type_legend},type', $GLOBALS['TL_DCA']['tl_content']['palettes']['text']);
        $this->assertSame('{title_legend},name,type', $GLOBALS['TL_DCA']['tl_module']['palettes']['navigation']);
    }

    /**
     * Ohne geladenes tl_content-DCA darf es keinen Fehler geben.
     *
     * Der Hook laeuft fuer jede Tabelle; das DCA muss nicht vorhanden sein.
     */
    public function testVertraegtFehlendePaletten(): void
    {
        (new SimpleColumnsPaletteHook())->onLoadDataContainer('tl_content');

        $this->assertArrayNotHasKey('TL_DCA', $GLOBALS);
    }
}
