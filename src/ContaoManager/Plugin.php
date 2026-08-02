<?php

declare(strict_types=1);

/**
 * SimpleColumns.
 *
 * @copyright  Frank Hoppe
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Schachbulle\ContaoSimpleColumnsBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Schachbulle\ContaoSimpleColumnsBundle\ContaoSimpleColumnsBundle;

/**
 * Meldet das Bundle beim Contao Manager an.
 */
class Plugin implements BundlePluginInterface
{
    /**
     * Gibt die Bundle-Konfiguration für den Contao Manager zurück.
     *
     * Das Bundle wird nach dem Contao-Core geladen, damit dessen DCA-Dateien bereits
     * vorliegen. Auf die Reihenfolge gegenüber anderen Erweiterungen kommt es nicht
     * an: Die Paletten werden erst im Hook "loadDataContainer" ergänzt, also nachdem
     * sämtliche DCA-Dateien eingebunden wurden.
     *
     * @param ParserInterface $parser Parser des Contao Managers, hier nicht benötigt
     *
     * @return array<BundleConfig> Liste mit der Konfiguration dieses einen Bundles
     */
    public function getBundles(ParserInterface $parser): array
    {
        return [
            BundleConfig::create(ContaoSimpleColumnsBundle::class)
                ->setLoadAfter([ContaoCoreBundle::class]),
        ];
    }
}
