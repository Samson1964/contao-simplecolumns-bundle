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
 * Prueft die Option "Zeile manuell schliessen" bei Spalten ueber mehrere Elemente.
 *
 * Hintergrund: Die Einstellung wird an dem Element gesetzt, das ein Spaltenset
 * beginnt. Wirken muss sie aber erst, wenn das Set zu Ende ist - und das letzte
 * Element eines Sets ist ein eingesammeltes Element ohne eigene Einstellungen.
 * Frueher wurde an dieser Stelle das falsche Element befragt, wodurch die Option
 * bei Spaltensets folgenlos blieb.
 *
 * Der Test braucht den Contao-Kern, weil der Hook von Contao\Frontend erbt. Ohne
 * gesetztes CONTAO_VENDOR wird er uebersprungen (siehe tests/bootstrap.php).
 */
class SimpleColumnsHookRowspanTest extends TestCase
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
     * Das Beispiel aus TODO.md: vier Elemente, zwei Spalten, je Spaltenhoehe 2.
     *
     * Element 1 und 3 beginnen je ein Spaltenset, Element 2 und 4 werden davon
     * eingesammelt. An Element 3 haengt "Zeile manuell schliessen" - der
     * Zeilenumbruch muss also nach Element 4 stehen, wenn das Set zu Ende ist.
     */
    public function testZeileManuellSchliessenWirktAmEndeDesSpaltensets(): void
    {
        $arrAusgabe = $this->render([
            ['simple_columns' => '2', 'simple_columns_rowspan' => 2],
            [],
            ['simple_columns' => '2', 'simple_columns_rowspan' => 2, 'simple_columns_close' => '1'],
            [],
        ]);

        $this->assertStringNotContainsString('sc-clear', $arrAusgabe[0], 'Element 1 beginnt das erste Set');
        $this->assertStringNotContainsString('sc-clear', $arrAusgabe[1], 'Element 2 gehoert noch zum ersten Set');
        $this->assertStringNotContainsString('sc-clear', $arrAusgabe[2], 'Element 3 beginnt erst das zweite Set');
        $this->assertStringContainsString('sc-clear', $arrAusgabe[3], 'Nach Element 4 endet das Set, hier gehoert der Umbruch hin');
    }

    /**
     * Ohne gesetzte Option darf kein Umbruch erzeugt werden.
     */
    public function testOhneOptionKeinUmbruch(): void
    {
        $arrAusgabe = $this->render([
            ['simple_columns' => '2', 'simple_columns_rowspan' => 2],
            [],
            ['simple_columns' => '2', 'simple_columns_rowspan' => 2],
            [],
        ]);

        foreach ($arrAusgabe as $intIndex => $strAusgabe) {
            $this->assertStringNotContainsString('sc-clear', $strAusgabe, 'Element '.($intIndex + 1));
        }
    }

    /**
     * Ohne Spaltenset zaehlt weiterhin die Einstellung am Element selbst.
     */
    public function testOhneSpaltensetWirktDieOptionAmElementSelbst(): void
    {
        $arrAusgabe = $this->render([
            ['simple_columns' => '2'],
            ['simple_columns' => '2', 'simple_columns_close' => '1'],
        ]);

        $this->assertStringNotContainsString('sc-clear', $arrAusgabe[0]);
        $this->assertStringContainsString('sc-clear', $arrAusgabe[1]);
    }

    /**
     * Das Umschlagelement eines Spaltensets traegt die Klasse sc-rowspan.
     *
     * Sie hing frueher an einem nie erreichbaren elseif-Zweig.
     */
    public function testUmschlagelementTraegtScRowspan(): void
    {
        $arrAusgabe = $this->render([
            ['simple_columns' => '2', 'simple_columns_rowspan' => 2],
            [],
        ]);

        $this->assertStringContainsString('sc-rowspan', $arrAusgabe[0]);
    }

    /**
     * Rendert eine Folge von Inhaltselementen durch den Hook.
     *
     * @param array<int, array<string, mixed>> $arrElemente Feldwerte je Element in
     *                                                      ihrer Sortierreihenfolge
     *
     * @return array<int, string> Das Ergebnis-HTML je Element
     */
    private function render(array $arrElemente): array
    {
        // Ohne Elternkonstruktor: Contao\Frontend wuerde die komplette Config

        // hochfahren. Die Hook-Methode nutzt keinen Zustand aus der Elternklasse.
        $objHook = (new \ReflectionClass(SimpleColumnsHook::class))->newInstanceWithoutConstructor();

        $arrAusgabe = [];

        foreach ($arrElemente as $arrData) {
            $arrAusgabe[] = $objHook->myGetContentElement($this->createElement($arrData), '<div class="ce_text block">Inhalt</div>');
        }

        return $arrAusgabe;
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
