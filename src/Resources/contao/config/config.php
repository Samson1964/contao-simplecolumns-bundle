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
 * @copyright  Lingo4you 2015
 * @author     Mario Müller <https://www.lingolia.com/>
 * @package    SimpleColumns
 * @license    http://opensource.org/licenses/lgpl-3.0.html
 */

$GLOBALS['simpleColumnCounter'] = array(2=>0, 3=>0, 4=>0, 5=>0, 6=>0, 7=>0, 8=>0, 9=>0, 10=>0);
$GLOBALS['simpleColumnRowspanCounter'] = 0;
// Merkt sich "Zeile manuell schliessen" vom Start eines Spaltensets, bis das Set endet
$GLOBALS['simpleColumnClose'] = false;
$GLOBALS['simpleColumnBeHtml'] = '';

$GLOBALS['TL_CONFIG']['simpleColumnsFramework'] = 'auto';
$GLOBALS['TL_CONFIG']['simpleColumnsBoxSizing'] = 'content-box';

$GLOBALS['SIMPLECOLUMNS'] = array
(
	'count' => 0,
	'style' => array
	(
		'content-box' 	=> 'bundles/contaosimplecolumns/styles/simple_columns.css',
		'border-box' 	=> 'bundles/contaosimplecolumns/styles/simple_columns_bb.css'
	),
	'close' => '<div class="sc-clear"></div>');

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['getContentElement'][] = array('\Schachbulle\ContaoSimpleColumnsBundle\Hooks\SimpleColumnsHook', 'myGetContentElement');

// Ergänzt die Spalten-Felder in allen Paletten von tl_content, nachdem sämtliche
// DCA-Dateien geladen wurden (unabhängig von der Bundle-Reihenfolge)
$GLOBALS['TL_HOOKS']['loadDataContainer'][] = array('\Schachbulle\ContaoSimpleColumnsBundle\Hooks\SimpleColumnsPaletteHook', 'onLoadDataContainer');
