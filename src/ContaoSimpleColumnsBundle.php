<?php

declare(strict_types=1);

/**
 * SimpleColumns.
 *
 * @copyright  Frank Hoppe
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

namespace Schachbulle\ContaoSimpleColumnsBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Symfony-Bundle-Klasse der Erweiterung.
 *
 * Die Erweiterung registriert bewusst keine eigenen Dienste im Container: Die
 * beiden Hooks werden klassisch über $GLOBALS['TL_HOOKS'] in
 * Resources/contao/config/config.php eingetragen und von Contao per
 * System::importStatic() erzeugt. Deshalb gibt es weder eine
 * DependencyInjection-Extension noch eine services.yaml.
 */
class ContaoSimpleColumnsBundle extends Bundle
{
}
