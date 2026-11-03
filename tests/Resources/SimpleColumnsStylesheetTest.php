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
 * Prüft die Spaltenbreiten der beiden mitgelieferten Stylesheets rechnerisch.
 *
 * Hintergrund (schachbund.de): Die Breiten von sc2 bis sc10 füllten den Platz
 * nicht vollständig aus. In der content-box-Datei sprengten feste Innenabstände
 * von 10px je Spalte jede Zeile (alle 1013 möglichen Aufteilungen brachen im
 * Browser um), in der border-box-Datei brachen 13 Aufteilungen bei 6 Spalten um
 * (6 x 16,67 % = 100,02 %), und die erste Spalte hatte 15px mehr Innenbreite als
 * die übrigen.
 *
 * Die Dateien sind jetzt aus einer Formel erzeugt (siehe Kopfkommentar in den
 * Stylesheets): Bei N Spalten ist eine Spalte C = (100 - 0,02 - (N - 1) * g) / N
 * breit, eine Spalte über k Spalten k * C + (k - 1) * g, mit g = 1,5 % Abstand.
 * Der Abstand ist der linke Rand jeder Spalte außer der ersten ihrer Zeile. Der
 * Test rechnet das nach, statt Zahlen zu wiederholen - so bleibt die Summenregel
 * auch dann geprüft, wenn jemand Abstand oder Reserve ändert.
 *
 * Beide Dateien müssen dieselbe Geometrie haben; sie unterscheiden sich nur im
 * Box-Sizing-Modell. Im Browser wurde dasselbe mit allen 1013 Aufteilungen bei
 * fünf Containerbreiten von 320 bis 1920px gemessen: kein Umbruch, Füllung
 * 99,95 bis 99,98 %, Abstände und Innenbreiten gleich.
 */
class SimpleColumnsStylesheetTest extends TestCase
{
    private const DIR = __DIR__.'/../../src/Resources/public/styles/';

    /**
     * Abstand zwischen zwei Spalten in Prozent der Zeilenbreite.
     */
    private const GAP = 1.5;

    /**
     * Reserve gegen Rundungsfehler der Browser in Prozent.
     */
    private const RESERVE = 0.02;

    /**
     * Liefert beide Stylesheets als Testdaten.
     *
     * @return array<string, array{0: string, 1: bool}> Dateiname und Angabe, ob border-box erwartet wird
     */
    public static function stylesheets(): iterable
    {
        return [
            'content-box' => ['simple_columns.css', false],
            'border-box' => ['simple_columns_bb.css', true],
        ];
    }

    /**
     * Jede der 45 Spaltenklassen (sc2 bis sc10, mit allen Spannen) hat die
     * berechnete Breite.
     *
     * @dataProvider stylesheets
     */
    public function testAlleBreitenEntsprechenDerFormel(string $strDatei, bool $blnBorderBox): void
    {
        $arrBreiten = $this->readWidths($strDatei);

        $intErwartet = 0;

        for ($n = 2; $n <= 10; ++$n) {
            for ($k = 1; $k < $n; ++$k) {
                $strKlasse = 1 === $k ? 'sc'.$n : 'sc'.$n.'-'.$k;
                ++$intErwartet;

                $this->assertArrayHasKey($strKlasse, $arrBreiten, 'Klasse .'.$strKlasse.' fehlt in '.$strDatei);
                $this->assertEqualsWithDelta(
                    $this->expectedWidth($n, $k),
                    $arrBreiten[$strKlasse],
                    0.0002,
                    'Breite von .'.$strKlasse.' in '.$strDatei.' weicht von der Formel ab',
                );
            }
        }

        $this->assertCount($intErwartet, $arrBreiten, 'Unerwartete zusätzliche Breitenklassen in '.$strDatei);
    }

    /**
     * Jede mögliche Zeilenaufteilung ergibt nahezu 100 %, aber nie mehr.
     *
     * Mehr als 100 % lässt die letzte Spalte in die nächste Zeile umbrechen (das
     * war der Fehler bei 6 Spalten), deutlich weniger verschenkt Platz. Geprüft
     * werden alle Zerlegungen von N in Teile 1 bis N-1, also genau die Kombinationen,
     * die die Auswahlliste im Backend anbietet.
     *
     * @dataProvider stylesheets
     */
    public function testJedeZeilenaufteilungFuelltDenPlatzOhneUeberlauf(string $strDatei, bool $blnBorderBox): void
    {
        $arrBreiten = $this->readWidths($strDatei);
        $intGeprueft = 0;

        for ($n = 2; $n <= 10; ++$n) {
            foreach ($this->compositions($n, $n - 1) as $arrTeile) {
                $fltSumme = 0.0;

                foreach ($arrTeile as $k) {
                    $fltSumme += $arrBreiten[1 === $k ? 'sc'.$n : 'sc'.$n.'-'.$k];
                }

                // Zwischen je zwei Spalten steht ein Abstand
                $fltSumme += (\count($arrTeile) - 1) * self::GAP;
                ++$intGeprueft;

                $strAufteilung = $n.' Spalten: '.implode('+', $arrTeile);

                $this->assertLessThanOrEqual(100 - self::RESERVE + 0.0005, $fltSumme, $strAufteilung.' in '.$strDatei.' überschreitet die Reserve');
                $this->assertGreaterThanOrEqual(99.95, $fltSumme, $strAufteilung.' in '.$strDatei.' lässt zu viel Platz ungenutzt');
            }
        }

        $this->assertSame(1013, $intGeprueft, 'Anzahl der Zeilenaufteilungen von 2 bis 10 Spalten');
    }

    /**
     * Der Abstand entsteht als linker Rand, nicht als Innenabstand.
     *
     * Ein Innenabstand an nur manchen Spalten macht deren Inhalt schmaler
     * (border-box) bzw. die Zeile zu breit (content-box). Daran scheiterte früher
     * die erste Spalte bzw. jede Zeile.
     *
     * @dataProvider stylesheets
     */
    public function testAbstandIstLinkerRandUndKeinInnenabstand(string $strDatei, bool $blnBorderBox): void
    {
        $strCss = $this->readCss($strDatei);

        $strSc = $this->block($strCss, '.sc');
        $this->assertMatchesRegularExpression('~margin-left:\s*1\.5%~', $strSc, '.sc braucht den Abstand als linken Rand');
        $this->assertMatchesRegularExpression('~float:\s*left~', $strSc);
        $this->assertDoesNotMatchRegularExpression('~padding-left:\s*[1-9]~', $strSc, '.sc darf keinen linken Innenabstand setzen');
        $this->assertDoesNotMatchRegularExpression('~padding-right:\s*[1-9]~', $strSc, '.sc darf keinen rechten Innenabstand setzen');

        $strFirst = $this->block($strCss, '.sc-first');
        $this->assertMatchesRegularExpression('~margin-left:\s*0~', $strFirst, 'Die erste Spalte einer Zeile hat keinen Abstand');
        $this->assertMatchesRegularExpression('~clear:\s*both~', $strFirst);
        $this->assertStringNotContainsString('padding-left', $strFirst, '.sc-first darf padding-left nicht überschreiben');
    }

    /**
     * Der Rand muss sich gegen Theme-Regeln durchsetzen, die den Rand von
     * Inhaltselementen per Kurzschreibweise auf 0 setzen.
     *
     * Ohne den Zusatz important löscht eine Theme-Regel mit höherer Spezifität den
     * linken Rand, und die Spalten stoßen ohne Abstand aneinander.
     *
     * @dataProvider stylesheets
     */
    public function testAbstandSetztSichGegenThemeRegelnDurch(string $strDatei, bool $blnBorderBox): void
    {
        $strCss = $this->readCss($strDatei);

        $this->assertMatchesRegularExpression('~margin-left:\s*1\.5%\s*!important~', $this->block($strCss, '.sc'));
        $this->assertMatchesRegularExpression('~margin-left:\s*0\s*!important~', $this->block($strCss, '.sc-first'));
    }

    /**
     * Nur die border-box-Datei schaltet das Box-Sizing um.
     *
     * @dataProvider stylesheets
     */
    public function testBoxSizingPasstZurDatei(string $strDatei, bool $blnBorderBox): void
    {
        $strSc = $this->block($this->readCss($strDatei), '.sc');

        if ($blnBorderBox) {
            $this->assertMatchesRegularExpression('~box-sizing:\s*border-box~', $strSc);
        } else {
            $this->assertStringNotContainsString('box-sizing', $strSc);
        }
    }

    /**
     * Berechnet die erwartete Breite nach der Formel aus dem Kopfkommentar.
     *
     * @param int $n Anzahl der Raster-Spalten der Zeile (2 bis 10)
     * @param int $k Anzahl der Raster-Spalten, die das Element überspannt
     *
     * @return float Breite in Prozent der Zeilenbreite
     */
    private function expectedWidth(int $n, int $k): float
    {
        $fltSpalte = (100 - self::RESERVE - ($n - 1) * self::GAP) / $n;

        return $k * $fltSpalte + ($k - 1) * self::GAP;
    }

    /**
     * Liest alle Breiten der Klassen sc2 bis sc10 aus einem Stylesheet.
     *
     * @return array<string, float> Klassenname ohne Punkt, z.B. "sc4-2", mit der Breite in Prozent
     */
    private function readWidths(string $strDatei): array
    {
        $intAnzahl = preg_match_all('~\.(sc\d+(?:-\d+)?)\s*\{\s*width:\s*([0-9.]+)%;\s*\}~', $this->readCss($strDatei), $arrTreffer, PREG_SET_ORDER);

        $this->assertGreaterThan(0, $intAnzahl, 'Breitenregeln in '.$strDatei.' nicht gefunden');

        $arrBreiten = [];

        foreach ($arrTreffer as $arrRegel) {
            $arrBreiten[$arrRegel[1]] = (float) $arrRegel[2];
        }

        return $arrBreiten;
    }

    /**
     * Liest ein Stylesheet ohne Kommentare.
     */
    private function readCss(string $strDatei): string
    {
        $strCss = file_get_contents(self::DIR.$strDatei);
        $this->assertNotFalse($strCss, 'Stylesheet nicht lesbar: '.$strDatei);

        return (string) preg_replace('~/\*.*?\*/~s', '', $strCss);
    }

    /**
     * Liefert den Inhalt der Regel mit genau diesem Selektor.
     *
     * @param string $strCss      Stylesheet ohne Kommentare
     * @param string $strSelektor Selektor, z.B. ".sc-first"
     */
    private function block(string $strCss, string $strSelektor): string
    {
        $this->assertSame(
            1,
            preg_match('~(?:^|\})\s*'.preg_quote($strSelektor, '~').'\s*\{([^}]*)\}~', $strCss, $arrTreffer),
            'Regel '.$strSelektor.' nicht gefunden',
        );

        return $arrTreffer[1];
    }

    /**
     * Zerlegt $n in Teile von 1 bis $max, die Reihenfolge zählt.
     *
     * @return array<int, array<int, int>> Alle Zerlegungen
     */
    private function compositions(int $n, int $max): array
    {
        if (0 === $n) {
            return [[]];
        }

        $arrErgebnis = [];

        for ($k = 1; $k <= min($n, $max); ++$k) {
            foreach ($this->compositions($n - $k, $max) as $arrRest) {
                $arrErgebnis[] = array_merge([$k], $arrRest);
            }
        }

        return $arrErgebnis;
    }
}
