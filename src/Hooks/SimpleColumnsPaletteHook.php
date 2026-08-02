<?php

declare(strict_types=1);

/**
 * SimpleColumns.
 *
 * Ergänzt die Spalten-Felder in allen Paletten von tl_content.
 *
 * Die Ergänzung erfolgt bewusst über den Hook "loadDataContainer" und nicht
 * direkt in der DCA-Datei: Contao lädt die DCA-Dateien aller Bundles in der
 * alphabetischen Reihenfolge der Paketnamen. Eine Ergänzung in
 * dca/tl_content.php erreicht daher nur die Paletten der Bundles, die vor
 * "schachbulle/contao-simplecolumns-bundle" geladen werden. Der Hook läuft
 * dagegen erst, nachdem sämtliche DCA-Dateien eingebunden wurden, und erfasst
 * damit alle Inhaltselemente – unabhängig vom Paketnamen.
 *
 * @copyright  Frank Hoppe
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Schachbulle\ContaoSimpleColumnsBundle\Hooks;

class SimpleColumnsPaletteHook
{
    /**
     * Felder, die an jede Palette angehängt werden.
     */
    private const PALETTE = '{simple_columns_legend},simple_columns,simple_columns_rowspan,simple_columns_close,simple_columns_wrapper,simple_columns_autoheight,simple_columns_border';

    /**
     * Hook "loadDataContainer".
     *
     * @param string $strTable Name der geladenen Tabelle
     */
    public function onLoadDataContainer($strTable): void
    {
        if ('tl_content' !== $strTable || empty($GLOBALS['TL_DCA']['tl_content']['palettes'])) {
            return;
        }

        foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $key => $palette) {
            // __selector__ und ähnliche Array-Einträge überspringen
            if (!\is_string($palette)) {
                continue;
            }

            // Palette wurde bereits ergänzt (z.B. durch mehrfachen Hook-Aufruf)
            if (str_contains($palette, 'simple_columns')) {
                continue;
            }

            if (str_contains($palette, '{expert_legend:hide}')) {
                $GLOBALS['TL_DCA']['tl_content']['palettes'][$key] = str_replace('{expert_legend:hide}', self::PALETTE.';{expert_legend:hide}', $palette);
            } elseif (str_contains($palette, '{protected_legend:hide}')) {
                $GLOBALS['TL_DCA']['tl_content']['palettes'][$key] = str_replace('{protected_legend:hide}', self::PALETTE.';{protected_legend:hide}', $palette);
            } else {
                $GLOBALS['TL_DCA']['tl_content']['palettes'][$key] = $palette.';'.self::PALETTE;
            }
        }
    }
}
