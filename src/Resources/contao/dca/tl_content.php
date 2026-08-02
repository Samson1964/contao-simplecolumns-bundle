<?php

/**
 * Contao Open Source CMS
 * Copyright (C) 2005-2010 Leo Feyer
 *
 * Formerly known as TYPOlight Open Source CMS.
 *
 * This program is free software: you can redistribute it and/or
 * modify it under the terms of the GNU Lesser General Public
 * License as published by the Free Software Foundation, either
 * version 3 of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU
 * Lesser General Public License for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this program. If not, please visit the Free
 * Software Foundation website at <http://www.gnu.org/licenses/>.
 *
 * PHP version 5
 * @copyright  Lingo4you 2014
 * @author     Mario Müller <http://www.lingolia.com/>
 * @package    SimpleColumns
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

use Contao\Backend;
use Contao\Database;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
{
	$GLOBALS['TL_DCA']['tl_content']['config']['onload_callback'][] = array('simpleColumns', 'onLoadCallback');
}

$GLOBALS['TL_DCA']['tl_content']['fields']['simple_columns'] = array
(
	'label'                           => &$GLOBALS['TL_LANG']['tl_content']['simple_columns']['title'],
	'exclude'                         => true,
	'inputType'                       => 'select',
	'options'                         => &$GLOBALS['TL_LANG']['tl_content']['simple_columns']['reference'],
	'eval' => array
	(
		'includeBlankOption'          => true,
		'maxlength'                   => 4,
		'tl_class'                    => 'w50'
	),
	'sql'                             => "char(4) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['simple_columns_rowspan'] = array
(
	'label'                           => &$GLOBALS['TL_LANG']['tl_content']['simple_columns_rowspan']['title'],
	'default'                         => '0',
	'exclude'                         => true,
	'inputType'                       => 'select',
	'options'                         => &$GLOBALS['TL_LANG']['tl_content']['simple_columns_rowspan']['reference'],
	'eval' => array
	(
		'tl_class'                    => 'w50'
	),
	'sql'                             => "int(10) unsigned NOT NULL default '0'"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['simple_columns_close'] = array
(
	'label'                           => &$GLOBALS['TL_LANG']['tl_content']['simple_columns_close'],
	'exclude'                         => true,
	'inputType'                       => 'checkbox',
	'eval' => array
	(
		'tl_class'                    => 'w50'
	),
	'sql'                             => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['simple_columns_wrapper'] = array
(
	'label'                           => &$GLOBALS['TL_LANG']['tl_content']['simple_columns_wrapper'],
	'exclude'                         => true,
	'inputType'                       => 'checkbox',
	'eval' => array
	(
		'tl_class'                    => 'w50'
	),
	'sql'                             => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['simple_columns_autoheight'] = array
(
	'label'                           => &$GLOBALS['TL_LANG']['tl_content']['simple_columns_autoheight'],
	'exclude'                         => true,
	'inputType'                       => 'checkbox',
	'eval' => array
	(
		'tl_class'                    => 'w50'
	),
	'sql'                             => "char(1) NOT NULL default ''"
);

$GLOBALS['TL_DCA']['tl_content']['fields']['simple_columns_border'] = array
(
	'label'                           => &$GLOBALS['TL_LANG']['tl_content']['simple_columns_border'],
	'exclude'                         => true,
	'inputType'                       => 'checkbox',
	'eval' 	=> array
	(
		'tl_class'                    => 'w50',
		'disabled'                    => (($GLOBALS['TL_CONFIG']['simpleColumnsBoxSizing'] ?? 'content-box') != 'border-box')
	),
	'sql'                             => "char(1) NOT NULL default ''"
);


// Hinweis: Die Ergänzung der Paletten erfolgt im Hook "loadDataContainer"
// (siehe Schachbulle\ContaoSimpleColumnsBundle\Hooks\SimpleColumnsPaletteHook).
// An dieser Stelle wären nur die Paletten der Bundles erreichbar, die vor
// diesem Bundle geladen werden.


/**
 * Callback-Klasse für die Spalteneinstellungen in tl_content.
 */
class simpleColumns extends Backend
{
	/**
	 * onload_callback: markiert die Spalteneinstellungen als wirkungslos.
	 *
	 * Gehört das bearbeitete Element zu einem Spaltenset, das ein vorheriges
	 * Element über die Option "Spaltenhöhe" aufgespannt hat, dann werden seine
	 * eigenen Spalteneinstellungen beim Rendern ignoriert. Damit das im Backend
	 * erkennbar ist, wird in dem Fall die Beschriftung der Legende gegen die
	 * Variante mit dem Warnhinweis getauscht.
	 *
	 * Dazu werden alle sichtbaren Elemente desselben Elternelements in ihrer
	 * Sortierreihenfolge durchlaufen, bis das bearbeitete Element erreicht ist,
	 * und dabei mitgezählt, wie weit ein begonnenes Spaltenset noch reicht.
	 *
	 * Seiteneffekt: Überschreibt $GLOBALS['TL_LANG']['tl_content']['simple_columns_legend'].
	 *
	 * @param object $dc DataContainer des bearbeiteten Datensatzes; $dc->id ist
	 *                   beim Anlegen eines neuen Elements noch leer, dann liefert
	 *                   die Abfrage kein Ergebnis und es passiert nichts
	 */
	public function onLoadCallback($dc): void
	{
		// Database::getInstance() statt $this->Database: der Zugriff auf importierte
		// Objekte ist seit Contao 5.2 als veraltet markiert.
		// fetchAllAssoc() statt der Ergebnis-Iteration mit magischen Properties –
		// so ist der Zugriff auf die Spalten für Menschen wie für Analysewerkzeuge
		// nachvollziehbar.
		$arrContent = Database::getInstance()->prepare('
			SELECT
				`id`,`simple_columns`,`simple_columns_rowspan`
			FROM
				`tl_content`
			WHERE
				`pid` = (SELECT `pid` FROM `tl_content` WHERE `id`=?) AND `invisible`=""
			ORDER BY
				`sorting`
		')->execute($dc->id)->fetchAllAssoc();

		$rowspan = 0;

		foreach ($arrContent as $arrRow)
		{
			// Beim bearbeiteten Element angekommen: was davor liegt, zählt
			if ($arrRow['id'] == $dc->id)
			{
				break;
			}

			if ($rowspan > 0)
			{
				$rowspan--;
			}
			elseif ($arrRow['simple_columns'] != '' && $arrRow['simple_columns_rowspan'] > 1)
			{
				$rowspan = (int) $arrRow['simple_columns_rowspan'] - 1;
			}
		}

		if ($rowspan > 0)
		{
			$GLOBALS['TL_LANG']['tl_content']['simple_columns_legend'] = $GLOBALS['TL_LANG']['tl_content']['simple_columns_legend_ignore'];
		}
	}

}
