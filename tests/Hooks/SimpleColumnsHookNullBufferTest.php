<?php

declare(strict_types=1);

/*
 * Dieses Bundle ordnet Inhaltselemente in Contao 4.13 und Contao 5 in Spalten an.
 *
 * @license LGPL-3.0-or-later
 */

namespace Schachbulle\ContaoSimpleColumnsBundle\Tests\Hooks;

use Contao\CoreBundle\Routing\ScopeMatcher;
use Contao\Frontend;
use Contao\System;
use PHPUnit\Framework\TestCase;
use Schachbulle\ContaoSimpleColumnsBundle\Hooks\SimpleColumnsHook;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestMatcherInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Prüft, dass der Hook einen leeren/fehlenden Puffer verträgt, statt mit einem
 * TypeError abzustürzen.
 *
 * Praxisfall (schachbund.de): echter 500er bei einem POST auf die Startseite.
 * Ursache: Contao reicht in der Hook-Kette "getContentElement" den Rückgabewert
 * des jeweils vorherigen Hooks weiter, ungeprüft und ohne Typdeklaration am
 * Parameter. Läuft vor diesem Hook ein anderer Baustein, der fälschlich null
 * statt eines leeren Strings zurückgibt, erhält myGetContentElement() null als
 * $strBuffer. Die Methode ist aber als string deklariert (strict_types=1
 * verbietet die automatische Umwandlung von null)
 * - das riss die Seite mit einem TypeError herunter. Ein zweiter, unabhängiger
 * Absturzpfad lag bei preg_match(), dessen Parameter ebenfalls string verlangt.
 * Beide Pfade sind mit echtem Rendering (kein Mock) nachgestellt.
 *
 * Der Test braucht den Contao-Kern, weil der Hook von Contao\Frontend erbt. Ohne
 * gesetztes CONTAO_VENDOR wird er uebersprungen (siehe tests/bootstrap.php).
 */
class SimpleColumnsHookNullBufferTest extends TestCase
{
    /**
     * Ueberspringt die Testklasse, wenn kein Contao-Kern geladen ist.
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        if (!class_exists(Frontend::class)) {
            static::markTestSkipped('Contao-Kern nicht geladen (CONTAO_VENDOR nicht gesetzt)');
        }
    }

    /**
     * Setzt Container und die seitenweiten Zaehler vor jedem Test neu auf.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objRequest = new Request();
        $objRequest->attributes->set('_scope', 'frontend');

        $objStack = new RequestStack();
        $objStack->push($objRequest);

        $objContainer = new Container(new ParameterBag(['kernel.debug' => false]));
        $objContainer->set('contao.routing.scope_matcher', new ScopeMatcher($this->createScopeMatcher('backend'), $this->createScopeMatcher('frontend'), $objStack));
        $objContainer->set('request_stack', $objStack);

        System::setContainer($objContainer);

        $GLOBALS['simpleColumnCounter'] = [2 => 0, 3 => 0, 4 => 0, 5 => 0, 6 => 0, 7 => 0, 8 => 0, 9 => 0, 10 => 0];
        $GLOBALS['simpleColumnRowspanCounter'] = 0;
        $GLOBALS['simpleColumnClose'] = false;
        $GLOBALS['simpleColumnBeHtml'] = '';
        $GLOBALS['SIMPLECOLUMNS'] = ['count' => 0, 'style' => '', 'close' => '<div class="sc-clear"></div>'];
        $GLOBALS['TL_CSS'] = [];
        $GLOBALS['TL_JAVASCRIPT'] = [];
    }

    /**
     * Ohne Spalteneinstellung und ohne laufendes Spaltenset gibt der Hook den Puffer
     * sofort zurück (kürzester Codepfad). Dieser Pfad wird für JEDES Element ohne
     * eigene Einstellung durchlaufen und war der Pfad aus dem Praxisfall.
     */
    public function testNullPufferOhneSpalteneinstellungWirdZuLeeremString(): void
    {
        $strErgebnis = $this->renderRaw([], null);

        $this->assertSame('', $strErgebnis);
    }

    /**
     * Ein Element mitten in einem Spaltenset über mehr als 2 Elemente (also weder
     * Start noch Ende) durchläuft einen anderen Codepfad, in dem $strBuffer
     * ungeprüft an preg_match() weitergereicht wird.
     */
    public function testNullPufferMittenInEinemSpaltensetWirdZuLeeremString(): void
    {
        $objHook = $this->createHook();

        $objHook->myGetContentElement($this->createElement(['simple_columns' => '2', 'simple_columns_rowspan' => 3]), '<div class="ce_text block">A</div>');
        $strErgebnis = $objHook->myGetContentElement($this->createElement([]), null);

        $this->assertSame('', $strErgebnis);
    }

    /**
     * Rendert ein einzelnes Element mit garantiert frischem Hook-Objekt.
     *
     * @param array<string, mixed> $arrData   Feldwerte des Inhaltselements
     * @param string|null          $strBuffer Zu übergebender Puffer, auch null möglich
     */
    private function renderRaw(array $arrData, string|null $strBuffer): string
    {
        return $this->createHook()->myGetContentElement($this->createElement($arrData), $strBuffer);
    }

    /**
     * Erstellt den Hook ohne Elternkonstruktor.
     *
     * Contao\Frontend wuerde sonst die komplette Config hochfahren; die Hook-Methode
     * nutzt keinen Zustand aus der Elternklasse.
     */
    private function createHook(): SimpleColumnsHook
    {
        return (new \ReflectionClass(SimpleColumnsHook::class))->newInstanceWithoutConstructor();
    }

    /**
     * Baut einen Ersatz fuer das ContentModel mit magischen Feldzugriffen.
     *
     * @param array<string, mixed> $arrData Feldwerte des Inhaltselements
     */
    private function createElement(array $arrData): object
    {
        return new class($arrData) {
            public function __construct(private array $arrData)
            {
            }

            public function __get(string $strKey)
            {
                return $this->arrData[$strKey] ?? null;
            }

            public function __set(string $strKey, $varValue): void
            {
                $this->arrData[$strKey] = $varValue;
            }

            public function __isset(string $strKey): bool
            {
                return isset($this->arrData[$strKey]);
            }
        };
    }

    /**
     * Liefert einen RequestMatcher, der genau auf einen Scope anspricht.
     *
     * @param string $strScope Der Scope, auf den der Matcher reagieren soll
     */
    private function createScopeMatcher(string $strScope): RequestMatcherInterface
    {
        return new class($strScope) implements RequestMatcherInterface {
            public function __construct(private string $strScope)
            {
            }

            public function matches(Request $objRequest): bool
            {
                return $this->strScope === $objRequest->attributes->get('_scope');
            }
        };
    }
}
