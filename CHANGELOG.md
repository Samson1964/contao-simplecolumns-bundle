# SimpleColumns Changelog

## Version 1.2.0 (2026-08-02)

* Fix: „Zeile manuell schließen" blieb bei Spalten über mehrere Elemente wirkungslos. Die Einstellung wird an dem Element gesetzt, das ein Spaltenset beginnt, wirken muss sie aber am Ende des Sets — und das letzte Element eines Sets ist ein eingesammeltes Element ohne eigene Einstellungen. Die Einstellung wird jetzt beim Start gemerkt und am Ende angewandt. (Die dafür vorgesehene Variable `$simpleColumnClose` wurde seit der Contao-2-Fassung zwar gesetzt, aber nie ausgewertet.)
* Fix: Die CSS-Klasse `sc-rowspan` wurde nie vergeben. Sie hing an einem `elseif`, das hinter einer Bedingung stand, die den Fall bereits abfing – damit lief `.sc-rowspan` aus dem Stylesheet ins Leere. Betrifft die Option „Spaltenhöhe".
* Fix: `src/Resources/config/services.yml` band über `_instanceof` das Interface `Symfony\Component\DependencyInjection\ContainerAwareInterface` ein, das es in Symfony 7 (Contao 5.4+) nicht mehr gibt. Da das Bundle keine eigenen Dienste braucht, sind services.yml und die DI-Extension entfallen.
* Change: Der seitenweite Zustand wird ausdrücklich über `$GLOBALS` angesprochen statt über `global`, damit im Code sichtbar ist, dass er den einzelnen Aufruf überdauert.
* Change: `declare(strict_types=1)` in allen Klassen, Contao Coding Standards durchgesetzt (`ecs.php`), ausführliche deutsche Kommentarblöcke an allen Methoden.
* Change: Der Hook ist in kleinere Methoden zerlegt (Stylesheet, Autohöhen-Skript, Backend-Vorschau, Klassen-Einbau), der auskommentierte Code aus der Contao-2-Fassung ist entfernt.
* Change: Datenbankabfrage im `onload_callback` liest über `fetchAllAssoc()` statt über magische Properties des Result-Objekts.
* Add: PHPUnit-Testsuite mit 12 Tests (`tests/`, `phpunit.xml.dist`) — für den Paletten-Hook sowie für Spaltensets und das Zeilenschließen. Die Contao-abhängigen Tests laufen mit `CONTAO_VENDOR=<pfad>/vendor`, sonst werden sie übersprungen.
* Change: Ungenutzte Alt-Pakete aus `require-dev` entfernt (`doctrine/doctrine-cache-bundle`, `php-http/guzzle6-adapter`, `php-http/message-factory`); stattdessen PHPUnit und der Contao-Coding-Standard.
* Geprüft mit PHP 8.3 gegen Contao 4.13.58 und Contao 5.7.7: PHPStan Level 6 ohne Befund, ECS ohne Befund, alle Tests grün.

## Version 1.1.1 (2026-07-30)

* Change: Beschreibung, Keywords und Homepage in der composer.json ergänzt, damit Packagist das Paket verständlich darstellt und über die Suche auffindbar macht

## Version 1.1.0 (2026-07-28)

* Fix: Spalteneinstellungen erscheinen jetzt in **allen** Inhaltselementen. Die Paletten werden über den Hook `loadDataContainer` ergänzt statt direkt in `dca/tl_content.php`. Bisher fehlten die Felder in allen Bundles, deren Paketname alphabetisch hinter `schachbulle/contao-simplecolumns-bundle` liegt (u.a. spielerregister, teamtournament, tributes, turnierbuero, tweet, twitch, volunteeringlist).
* Fix: Elemente ohne passendes Start-Tag (Templates, die direkt mit `<table>`, `<ul>`, `<p>` o.ä. beginnen) werden jetzt automatisch in ein DIV eingeschlossen, statt die Spalteneinstellung stillschweigend zu ignorieren.
* Fix: Fataler Fehler unter Contao 5 / PHP 8 bei aktivierter Option „Gleiche Spaltenhöhe" (`Undefined constant "VERSION"`).
* Fix: Auswahlwerte mit 10 Spalten (`10` bis `10-9`) wurden falsch ausgewertet, dadurch wurde die Zeile nie umgebrochen.
* Fix: PHP-8-Warnungen bei leerer CSS-ID (`Trying to access array offset on null`, `strlen(): Passing null`).
* Fix: Warnungen bei nicht gesetzten Systemeinstellungen `simpleColumnsBoxSizing` / `simpleColumnsFramework`.
* Fix: Fehlende CSS-Klasse `.sc6-5`; Breiten der 6-spaltigen Bereichsklassen korrigiert (waren aus dem 5-Spalten-Satz kopiert).
* Change: PHP-Anforderung auf `^7.4 || ^8.0` angehoben.

## Version 1.0.5 (2025-03-13)

* Fix: Debug-Anweisung im CSS

## Version 1.0.4 (2025-03-13)

* Change: simple_columns.css angefangen leicht zu überarbeiten

## Version 1.0.3 (2025-03-13)

* Merge pull request #3 from lukasbableck/patch-1: Contao 5 compatibility

## Version 1.0.2 (2022-11-11)

* Change: Abhängigkeit PHP-Version aufgehoben

## Version 1.0.1 (2022-11-09)

* Fix: Kompatibilität mit PHP 8 hergestellt

## Version 1.0.0 (2022-03-18)

* Add: Ausbau von 5 auf 10 Spalten
* Fix: Pfade zu den öffentlichen Dateien
* Fix: close.gif eingebunden aus Contao 2

## Version 0.0.1 (2022-03-17)

Migration der Version 1.7.0-RC1 von Mario Müller
