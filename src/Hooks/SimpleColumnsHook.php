<?php

declare(strict_types=1);

/**
 * SimpleColumns.
 *
 * Basiert auf der Contao-2-Erweiterung von Mario Müller (Lingo4you).
 *
 * @copyright  Lingo4you 2015, Frank Hoppe
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Schachbulle\ContaoSimpleColumnsBundle\Hooks;

use Contao\Frontend;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

/**
 * Hängt die Spalten-CSS-Klassen an das gerenderte HTML eines Inhaltselements.
 */
class SimpleColumnsHook extends Frontend
{
    /**
     * Erstes Start-Tag im Element, an das die Spaltenklassen gehängt werden.
     *
     * Absichtlich auf Block-Elemente beschränkt: Ein float-Layout an einem
     * Inline-Element (z.B. <span>) hätte keine Wirkung. Findet sich keines dieser
     * Tags, wird das Element stattdessen in ein eigenes DIV gefasst.
     */
    private const TAG_PATTERN = '~(.*?)(?!<[a-z]+ class="no-no)(<(?:div|nav|section|h[1-6])[^>]*>)(.*)~ism';

    /**
     * Hook "getContentElement": ergänzt die Spaltenklassen im Element-HTML.
     *
     * Die Methode wird für jedes Inhaltselement einer Seite aufgerufen. Ist am
     * Element keine Spaltenzahl hinterlegt und läuft auch kein Spaltenset über
     * mehrere Elemente, wird der Puffer unverändert zurückgegeben.
     *
     * Im Frontend werden die Klassen in das erste Block-Start-Tag des Elements
     * geschrieben (siehe TAG_PATTERN) bzw. das Element in ein DIV gefasst. Im Backend
     * wird stattdessen eine kleine Grafikleiste vorangestellt, die die
     * Spaltenaufteilung im Artikelbaum sichtbar macht.
     *
     * Seiteneffekte: Der Zähler pro Spaltenzahl, der Rowspan-Zähler und das
     * gemerkte Backend-HTML liegen in Globals (definiert in config/config.php) und
     * werden hier fortgeschrieben – die Methode ist also bewusst zustandsbehaftet
     * über alle Elemente einer Seite hinweg. Zusätzlich werden bei Bedarf das
     * Stylesheet in $GLOBALS['TL_CSS'] und das Autohöhen-Skript in
     * $GLOBALS['TL_JAVASCRIPT'] eingetragen.
     *
     * @param object      $objElement        Datensatz des Inhaltselements (ContentModel)
     * @param string|null $strBuffer         Gerendertes HTML des Inhaltselements. In der
     *                                       Signatur nicht als string deklariert (so gibt
     *                                       Contao es an alle getContentElement-Hooks
     *                                       weiter) und kann null sein, wenn ein anderer,
     *                                       vorher in der Hook-Kette laufender Baustein
     *                                       fälschlich null statt eines leeren Strings
     *                                       liefert
     * @param object|null $objContentElement Inhaltselement-Objekt; erst ab Contao 5
     *                                       übergeben und hier nicht benötigt
     *
     * @return string Das angepasste HTML, im Fehlerfall der unveränderte Puffer
     */
    public function myGetContentElement($objElement, $strBuffer, $objContentElement = null): string
    {
        global $objPage;

        // Normiert auf einen garantierten String, bevor irgendetwas mit $strBuffer passiert:

        // Contaos Hook-Kette reicht den Rückgabewert des jeweils vorherigen

        // getContentElement-Hooks weiter, ungeprüft und ohne Typdeklaration. Liefert ein

        // anderer registrierter Hook fälschlich null statt eines leeren Strings, stürzte

        // diese Methode bisher an zwei Stellen ab: direkt bei preg_match() weiter unten

        // (TypeError, da dessen Parameter string verlangt) und beim Rückgabewert selbst

        // (TypeError, da die Methode als string deklariert ist, strict_types aber keine

        // automatische Umwandlung von null erlaubt). In der Praxis (schachbund.de) als echter

        // 500er bei einem POST auf die Startseite aufgetreten.
        $strBuffer = (string) $strBuffer;

        // Der Zustand über alle Elemente einer Seite hinweg liegt bewusst in Globals

        // (vorbelegt in Resources/contao/config/config.php) und wird hier ausdrücklich

        // über $GLOBALS angesprochen: So ist an jeder Stelle sichtbar, dass der Wert

        // den einzelnen Aufruf überdauert.
        $intRowspanCounter = (int) ($GLOBALS['simpleColumnRowspanCounter'] ?? 0);

        $strColumns = (string) ($objElement->simple_columns ?? '');

        // Weder eigene Spalteneinstellung noch laufendes Spaltenset
        if ('' === $strColumns && $intRowspanCounter < 2) {
            return $strBuffer;
        }

        $match = [];
        $blnTagFound = (bool) preg_match(self::TAG_PATTERN, $strBuffer, $match);

        // Findet sich im Element kein passendes Start-Tag (z.B. bei Templates, die direkt mit

        // <table>, <ul> oder <p> beginnen), wird das Element automatisch in ein DIV

        // eingeschlossen. Sonst würde die Spalteneinstellung wirkungslos verpuffen.
        $blnWrapper = (bool) $objElement->simple_columns_wrapper || !$blnTagFound;

        $this->addStylesheet();

        $startRowspan = false;
        $closeRowspan = false;
        $simpleColumnRowspan = false;

        if ($intRowspanCounter > 2) {
            // Element mitten in einem Spaltenset: nur herunterzählen
            $GLOBALS['simpleColumnRowspanCounter'] = $intRowspanCounter - 1;
            $simpleColumnRowspan = true;
        } elseif (2 === $intRowspanCounter) {
            // Letztes Element des Spaltensets: Umschlagelement wird geschlossen
            $closeRowspan = true;
            $simpleColumnRowspan = true;
            $GLOBALS['simpleColumnRowspanCounter'] = 0;
        } elseif ($objElement->simple_columns_rowspan > 1) {
            // Erstes Element eines neuen Spaltensets
            $GLOBALS['simpleColumnRowspanCounter'] = (int) $objElement->simple_columns_rowspan;
            $startRowspan = true;
            $simpleColumnRowspan = true;
        }

        $scClass = 'sc sc'.$strColumns.' sc-count'.$GLOBALS['SIMPLECOLUMNS']['count']++.($objElement->simple_columns_border ? ' sc-border' : '');

        if ($objElement->simple_columns_autoheight) {
            $this->addAutoheightScript($objPage);
            $scClass .= ' sc-autoheight';
        }

        // Auswahlwert auflösen: "4" = 4 Spalten / 1 Feld, "10-9" = 10 Spalten / 9 Felder
        $arrColumns = explode('-', $strColumns);
        $columns = (int) $arrColumns[0];
        $columnCount = isset($arrColumns[1]) ? (int) $arrColumns[1] : 1;

        if ($columnCount < 1) {
            $columnCount = 1;
        }

        // Bereits belegte Spalten der laufenden Zeile. Der Zähler kommt aus einer

        // Global und wird hier auf int normiert – zum einen wegen unbekannter

        // Spaltenzahlen aus alten Datenbeständen, zum anderen weil an der Global

        // theoretisch jeder Wert hängen kann.
        $intUsed = (int) ($GLOBALS['simpleColumnCounter'][$columns] ?? 0);

        $be_html = $this->isBackend()
            ? $this->generateBackendPreview($intUsed, $columns, $columnCount, (bool) $objElement->simple_columns_close, $simpleColumnRowspan)
            : '<div>';

        if ($startRowspan) {
            $GLOBALS['simpleColumnBeHtml'] = $be_html;
        }

        if (!$simpleColumnRowspan || $startRowspan) {
            if (0 === $intUsed) {
                $scClass .= ' sc-first sc'.$strColumns.'-first';
                $intUsed += $columnCount;
            } elseif ($intUsed < $columns - $columnCount) {
                $intUsed += $columnCount;
            } else {
                $scClass .= ' sc-last sc'.$strColumns.'-last';
                $objElement->simple_columns_close = true;
            }
        }

        $GLOBALS['simpleColumnCounter'][$columns] = $intUsed;

        // Die Einstellung "Zeile manuell schließen" wird erst jetzt gemerkt, nachdem

        // die automatische sc-last-Erkennung oben gelaufen ist: Belegt das Spaltenset

        // die letzte Spalte der Zeile vollständig, setzt der Block darüber

        // $objElement->simple_columns_close automatisch auf true - unabhängig davon, ob

        // der Autor den Haken manuell gesetzt hat. Wurde $GLOBALS['simpleColumnClose']

        // schon beim Start des Sets erfasst (wie früher), ging dieser Automatismus

        // verloren: Das Umschlagelement bekam nie den Abschluss-Marker, der

        // abschließende Clearfix fehlte, und das nächste normale Element rutschte

        // optisch unter die noch schwebenden Spalten.
        if ($startRowspan) {
            $GLOBALS['simpleColumnClose'] = (bool) $objElement->simple_columns_close;
        }

        // Das Umschlagelement eines Spaltensets bekommt zusätzlich die Klasse

        // sc-rowspan. Bisher stand das als elseif hinter der Bedingung darüber und war

        // damit nie erreichbar: Ist $startRowspan gesetzt, greift immer schon der

        // if-Zweig. Die Klasse wurde deshalb nie vergeben und .sc-rowspan aus dem

        // Stylesheet lief ins Leere.
        if ($startRowspan) {
            $scClass = 'sc-rowspan '.$scClass;
        }

        if ($objElement->simple_columns_close) {
            $scClass .= ' sc-close';
            $GLOBALS['simpleColumnCounter'][$columns] = 0;
        }

        if (!$this->isFrontend()) {
            return ($simpleColumnRowspan ? $GLOBALS['simpleColumnBeHtml'] : $be_html).'</div>'.$strBuffer;
        }

        if ($startRowspan) {
            // Beim Umschlagelement eines Spaltensets die CSS-Klasse des Elements mitnehmen,

            // sonst hinge sie am inneren Element
            $cssIdClass = StringUtil::deserialize($objElement->cssID, true);

            if (!empty($cssIdClass[1])) {
                $scClass .= ' '.$cssIdClass[1];
            }

            $strBuffer = '<div class="'.$scClass.'">'.$strBuffer;
        } elseif ($closeRowspan) {
            $strBuffer .= '</div>';
        } elseif (!$simpleColumnRowspan) {
            $strBuffer = $blnWrapper
                ? '<div class="'.$scClass.' sc-wrapper">'.$strBuffer.'</div>'
                : $this->injectClass($match, $scClass);
        }

        // Ob die Zeile hier endet. Beim letzten Element eines Spaltensets zaehlt die

        // beim Start gemerkte Einstellung: Das letzte Element ist ein vom Set

        // eingesammeltes Element ohne eigene Spalteneinstellungen, sein eigenes Feld

        // simple_columns_close ist also immer leer. Ohne diese Uebernahme blieb "Zeile

        // manuell schliessen" bei Spaltensets wirkungslos.
        if ($closeRowspan) {
            $blnClose = (bool) ($GLOBALS['simpleColumnClose'] ?? false);
            $GLOBALS['simpleColumnClose'] = false;
        } else {
            $blnClose = (bool) $objElement->simple_columns_close;
        }

        if (!empty($GLOBALS['SIMPLECOLUMNS']['close']) && $blnClose && (!$simpleColumnRowspan || $closeRowspan)) {
            $strBuffer .= $GLOBALS['SIMPLECOLUMNS']['close'];
        }

        return $strBuffer;
    }

    /**
     * Schreibt die Spaltenklassen in das gefundene Start-Tag.
     *
     * Bringt das Tag bereits ein class-Attribut mit, werden die Klassen dort
     * angehängt, andernfalls wird ein neues class-Attribut eingefügt.
     *
     * @param array<int, string> $match   Treffer aus TAG_PATTERN: 1 = Text davor,
     *                                    2 = Start-Tag, 3 = Rest des Elements
     * @param string             $scClass Die einzufügenden CSS-Klassen
     *
     * @return string Das wieder zusammengesetzte HTML des Elements
     */
    private function injectClass(array $match, string $scClass): string
    {
        $count = 0;
        $strTag = preg_replace('~(class="[^"]*)"~iU', '$1 '.$scClass.'"', $match[2], 1, $count);

        if ($count < 1) {
            $strTag = str_replace('>', ' class="'.$scClass.'">', $match[2]);
        }

        return $match[1].$strTag.$match[3];
    }

    /**
     * Bindet das mitgelieferte Stylesheet ein, sofern noch nicht geschehen.
     *
     * Welche der beiden Dateien geladen wird, hängt vom in den Systemein- stellungen
     * gewählten Box-Sizing-Modell ab. Nach dem ersten Aufruf wird
     * $GLOBALS['SIMPLECOLUMNS']['style'] geleert, damit die Datei nicht bei jedem
     * weiteren Element erneut eingetragen wird. Wer eigene Styles verwenden möchte,
     * kann den Eintrag in der localconfig.php leeren.
     */
    private function addStylesheet(): void
    {
        $strBoxSizing = $GLOBALS['TL_CONFIG']['simpleColumnsBoxSizing'] ?? 'content-box';

        if (!empty($GLOBALS['SIMPLECOLUMNS']['style']) && isset($GLOBALS['SIMPLECOLUMNS']['style'][$strBoxSizing])) {
            $GLOBALS['TL_CSS'][] = $GLOBALS['SIMPLECOLUMNS']['style'][$strBoxSizing].'||static';
            $GLOBALS['SIMPLECOLUMNS']['style'] = '';
        }
    }

    /**
     * Bindet das Skript für die Option "Gleiche Spaltenhöhe" ein.
     *
     * Das Skript wird nur einmal pro Seitenaufruf eingebunden, was über die
     * Konstante SIMPLE_COLUMNS_JS_LINK sichergestellt wird. Bei der Einstellung
     * "auto" entscheidet das im Seitenlayout aktivierte Framework; da Contao 5 kein
     * MooTools mehr mitbringt, landet man dort praktisch immer bei jQuery.
     *
     * @param object|null $objPage Aktuelle Seite; liefert die Flags hasMooTools
     *                             und hasJQuery aus dem Seitenlayout. Im Backend
     *                             oder bei Direktaufrufen null – dann greift die
     *                             ausdrückliche Einstellung oder es wird kein
     *                             Skript eingebunden
     */
    private function addAutoheightScript($objPage): void
    {
        if (\defined('SIMPLE_COLUMNS_JS_LINK')) {
            return;
        }

        $strFramework = $GLOBALS['TL_CONFIG']['simpleColumnsFramework'] ?? 'auto';

        if ('mootools' === $strFramework || ('auto' === $strFramework && !empty($objPage->hasMooTools))) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaosimplecolumns/scripts/moo_simple_columns.js';
        } elseif ('jquery' === $strFramework || !empty($objPage->hasJQuery)) {
            $GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaosimplecolumns/scripts/jquery_simple_columns.js';
        }

        \define('SIMPLE_COLUMNS_JS_LINK', 1);
    }

    /**
     * Baut die Grafikleiste, die im Backend die Spaltenaufteilung anzeigt.
     *
     * Vor der belegten Spalte stehen so viele Platzhalter, wie in der Zeile bereits
     * belegt sind; dahinter wird bis zum Zeilenende aufgefüllt bzw. bei einer
     * manuell geschlossenen Zeile das Schließen-Symbol gesetzt.
     *
     * @param int  $intUsed     Bereits belegte Spalten der aktuellen Zeile
     * @param int  $columns     Gesamtzahl der Spalten der Zeile
     * @param int  $columnCount Anzahl der Spalten, die dieses Element belegt
     * @param bool $blnClose    Zeile wird nach diesem Element manuell geschlossen
     * @param bool $blnRowspan  Element gehört zu einem Spaltenset über mehrere
     *                          Elemente; die Symbole werden dann höher gezeichnet
     *
     * @return string HTML der Grafikleiste, ohne das schließende </div>
     */
    private function generateBackendPreview(int $intUsed, int $columns, int $columnCount, bool $blnClose, bool $blnRowspan): string
    {
        $intMargin = $blnRowspan ? '12' : '2';
        $strEmpty = '<img src="bundles/contaosimplecolumns/images/empty.png" style="margin:2px 2px '.$intMargin.'px 2px; width:10px; height:10px;" alt="">';

        $be_html = '<div>';

        for ($i = 0; $i < $intUsed; ++$i) {
            $be_html .= $strEmpty;
        }

        $be_html .= '<img src="bundles/contaosimplecolumns/images/column.png" style="margin:2px; width:'.($columnCount * 10 + ($columnCount - 1) * 4).'px; height:'.($blnRowspan ? '20' : '10').'px;" alt="">';

        if ($blnClose) {
            $be_html .= '<img src="bundles/contaosimplecolumns/images/close.gif" style="margin:2px; width:10px; height:10px;" alt="">';
        } else {
            for ($i = $intUsed + $columnCount; $i < $columns; ++$i) {
                $be_html .= $strEmpty;
            }
        }

        return $be_html;
    }

    /**
     * Prüft, ob der aktuelle Aufruf aus dem Backend kommt.
     *
     * Fehlt ein Request (etwa bei Aufrufen über die Kommandozeile), wird ersatzweise ein
     * leerer Request erzeugt; der gilt weder als Backend- noch als Frontend-Anfrage.
     *
     * @return bool true, wenn es sich um eine Backend-Anfrage handelt
     */
    private function isBackend(): bool
    {
        return System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest($this->getRequest());
    }

    /**
     * Prüft, ob der aktuelle Aufruf aus dem Frontend kommt.
     *
     * @return bool true, wenn es sich um eine Frontend-Anfrage handelt
     */
    private function isFrontend(): bool
    {
        return System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest($this->getRequest());
    }

    /**
     * Liefert den aktuellen Request oder einen leeren Ersatz-Request.
     *
     * @return Request Der Request aus dem Stack, sonst ein leerer Request
     */
    private function getRequest(): Request
    {
        return System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('');
    }
}
