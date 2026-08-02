<?php

declare(strict_types=1);

/*
 * Dieses Bundle ordnet Inhaltselemente in Contao 4.13 und Contao 5 in Spalten an.
 *
 * @license LGPL-3.0-or-later
 */

/*
 * Autoloader fuer die Testsuite.
 *
 * Das Bundle wird ohne eigenes vendor/-Verzeichnis versioniert, deshalb steht
 * hier ein schlanker PSR-4-Autoloader statt vendor/autoload.php. Er reicht aus,
 * solange nur Klassen getestet werden, die ohne Contao-Kern auskommen.
 * PHPUnit selbst wird aus der gemeinsamen Installation aufgerufen:
 *
 *     php F:/Claude/tools/phpunit9/vendor/bin/phpunit
 *
 * Ein Teil der Tests braucht den Contao-Kern (der Frontend-Hook erbt von
 * Contao\Frontend). Diese Tests werden uebersprungen, solange keine Contao-
 * Installation angegeben ist. Mit Angabe laufen sie mit:
 *
 *     CONTAO_VENDOR=F:/Claude/contao-test/vendor php .../phpunit
 */
if (($strVendor = getenv('CONTAO_VENDOR')) && is_file($strVendor.'/autoload.php')) {
    require $strVendor.'/autoload.php';
}

spl_autoload_register(
    static function (string $strClass): void {
        $arrPrefixes = [
            'Schachbulle\\ContaoSimpleColumnsBundle\\Tests\\' => __DIR__.'/',
            'Schachbulle\\ContaoSimpleColumnsBundle\\' => __DIR__.'/../src/',
        ];

        foreach ($arrPrefixes as $strPrefix => $strBaseDir) {
            if (!str_starts_with($strClass, $strPrefix)) {
                continue;
            }

            $strFile = $strBaseDir.str_replace('\\', '/', substr($strClass, strlen($strPrefix))).'.php';

            if (is_file($strFile)) {
                require $strFile;

                return;
            }
        }
    },
);
