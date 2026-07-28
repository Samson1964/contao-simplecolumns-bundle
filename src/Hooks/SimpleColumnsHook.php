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

namespace Schachbulle\ContaoSimpleColumnsBundle\Hooks;

use Contao\Frontend;
use Contao\StringUtil;
use Contao\System;
use Symfony\Component\HttpFoundation\Request;

class SimpleColumnsHook extends Frontend
{
	/**
	 * Hook "getContentElement"
	 *
	 * Contao 5 übergibt zusätzlich das Inhaltselement-Objekt als dritten
	 * Parameter, Contao 4.13 nur die ersten beiden.
	 *
	 * @param object $objElement Datensatz des Inhaltselements (ContentModel)
	 * @param string $strBuffer  Gerendertes HTML des Inhaltselements
	 *
	 * @return string
	 */
	public function myGetContentElement($objElement, $strBuffer, $objContentElement = null)
	{
		/* defined in config/config.php */
		global $simpleColumnCounter, $simpleColumnRowspanCounter, $simpleColumnClose, $simpleColumnBeHtml, $objPage;

		$strColumns = (string) ($objElement->simple_columns ?? '');

		if ($strColumns === '' && $simpleColumnRowspanCounter < 2)
		{
			return $strBuffer;
		}

		/**
		 * fix for site_export extension because rendering twice
		 */
/*
		if (($this->Input->get('export') == '1' && $this->Input->get('layout') != '') && !isset($GLOBALS['SITE_EXPORT']))
		{
			return $strBuffer;
		}
*/
		$match = array();
		$blnTagFound = (bool) preg_match('~(.*?)(?!<[a-z]+ class="no-no)(<(?:div|nav|section|h[1-6])[^>]*>)(.*)~ism', $strBuffer, $match);

		// Findet sich im Element kein passendes Start-Tag (z.B. bei Templates,
		// die direkt mit <table>, <ul> oder <p> beginnen), wird das Element
		// automatisch in ein DIV eingeschlossen. Sonst würde die Spalten-
		// einstellung wirkungslos verpuffen.
		$blnWrapper = (bool) $objElement->simple_columns_wrapper || !$blnTagFound;

		$strBoxSizing = $GLOBALS['TL_CONFIG']['simpleColumnsBoxSizing'] ?? 'content-box';

		if (!empty($GLOBALS['SIMPLECOLUMNS']['style']) && isset($GLOBALS['SIMPLECOLUMNS']['style'][$strBoxSizing]))
		{
			$GLOBALS['TL_CSS'][] = $GLOBALS['SIMPLECOLUMNS']['style'][$strBoxSizing].'||static';
			$GLOBALS['SIMPLECOLUMNS']['style'] = '';
		}

		if ($strColumns !== '' || $simpleColumnRowspanCounter > 1)
		{
			$startRowspan = false;
			$closeRowspan = false;
			$simpleColumnRowspan = false;

			if ($simpleColumnRowspanCounter > 2)
			{
				$simpleColumnRowspanCounter--;
				$simpleColumnRowspan = true;
			}
			elseif ($simpleColumnRowspanCounter == 2)
			{
				$closeRowspan = true;
				$simpleColumnRowspan = true;
				$simpleColumnRowspanCounter = 0;
			}
			elseif ($objElement->simple_columns_rowspan > 1)
			{
				$simpleColumnRowspanCounter = $objElement->simple_columns_rowspan;
				$simpleColumnClose = $objElement->simple_columns_close;
				$startRowspan = true;
				$simpleColumnRowspan = true;
			}

			$be_html = '<div>';

			$scClass = 'sc sc' . $strColumns . ' sc-count'.$GLOBALS['SIMPLECOLUMNS']['count']++ . ($objElement->simple_columns_border?' sc-border':'');

			if ($objElement->simple_columns_autoheight)
			{
				if (!defined('SIMPLE_COLUMNS_JS_LINK'))
				{
					$strFramework = $GLOBALS['TL_CONFIG']['simpleColumnsFramework'] ?? 'auto';

					if ($strFramework == 'mootools' || ($strFramework == 'auto' && !empty($objPage->hasMooTools)))
					{
						$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaosimplecolumns/scripts/moo_simple_columns.js';
					}
					elseif ($strFramework == 'jquery' || !empty($objPage->hasJQuery))
					{
						$GLOBALS['TL_JAVASCRIPT'][] = 'bundles/contaosimplecolumns/scripts/jquery_simple_columns.js';
					}

					define('SIMPLE_COLUMNS_JS_LINK', 1);
				}

				$scClass .= ' sc-autoheight';
			}

			// Auswahlwert auflösen: "4" = 4 Spalten / 1 Feld, "10-9" = 10 Spalten / 9 Felder
			$arrColumns = explode('-', $strColumns);
			$columns = (int) $arrColumns[0];
			$columnCount = isset($arrColumns[1]) ? (int) $arrColumns[1] : 1;

			// Unbekannte Spaltenzahl (z.B. durch alte Datenbestände) abfangen
			if (!isset($simpleColumnCounter[$columns]))
			{
				$simpleColumnCounter[$columns] = 0;
			}

			if ($columnCount < 1)
			{
				$columnCount = 1;
			}

			if (System::getContainer()->get('contao.routing.scope_matcher')->isBackendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
			{
				for ($i=0; $i < $simpleColumnCounter[$columns]; $i++)
				{
					$be_html .= '<img src="bundles/contaosimplecolumns/images/empty.png" style="margin:2px 2px '.($simpleColumnRowspan?'12':'2').'px 2px; width:10px; height:10px;" alt="">';
				}

				$be_html .= '<img src="bundles/contaosimplecolumns/images/column.png" style="margin:2px; width:'.($columnCount*10+($columnCount-1)*4).'px; height:'.($simpleColumnRowspan?'20':'10').'px;" alt="" style="margin:2px">';

				if ($objElement->simple_columns_close)
				{
					$be_html .= '<img src="bundles/contaosimplecolumns/images/close.gif" style="margin:2px; width:10px; height:10px;" alt="">';
				}
				else
				{
					for($i = $simpleColumnCounter[$columns] + $columnCount; $i < $columns; $i++)
					{
						$be_html .= '<img src="bundles/contaosimplecolumns/images/empty.png" style="margin:2px 2px '.($simpleColumnRowspan?'12':'2').'px 2px; width:10px; height:10px;" alt="">';
					}
				}
			}


			if ($startRowspan)
			{
				$simpleColumnBeHtml = $be_html;
			}


			if (!$simpleColumnRowspan || $startRowspan)
			{
				if ($simpleColumnCounter[$columns] == 0)
				{
					$scClass .= ' sc-first sc' . $strColumns . '-first';
					$simpleColumnCounter[$columns] += $columnCount;
				}
				elseif ($simpleColumnCounter[$columns] < $columns-$columnCount)
				{
					$simpleColumnCounter[$columns] += $columnCount;
				}
				else
				{
					$scClass .= ' sc-last sc' . $strColumns . '-last';
					$objElement->simple_columns_close = true;
				}
			}
			elseif ($startRowspan)
			{
				$scClass = 'sc-rowspan '.$scClass;
			}

			if ($objElement->simple_columns_close)
			{
				$scClass .= ' sc-close';
				$simpleColumnCounter[$columns] = 0;
			}

			if (System::getContainer()->get('contao.routing.scope_matcher')->isFrontendRequest(System::getContainer()->get('request_stack')->getCurrentRequest() ?? Request::create('')))
			{
				if ($startRowspan)
				{
					$cssIdClass = StringUtil::deserialize($objElement->cssID, true);

					if (!empty($cssIdClass[1]))
					{
						$scClass .= ' '.$cssIdClass[1];
					}

					$strBuffer = '<div class="'.$scClass.'">'.$strBuffer;
				}
				elseif ($closeRowspan)
				{
					$strBuffer = $strBuffer.'</div>';
				}
				elseif (!$simpleColumnRowspan)
				{
					$count = 0;

					if ($blnWrapper)
					{
						$strBuffer = '<div class="'.$scClass.' sc-wrapper">'.$strBuffer.'</div>';
					}
					else
					{
						$match[2] = preg_replace('~(class="[^"]*)"~iU', '$1 '.$scClass.'"', $match[2], 1, $count);

						if ($count < 1)
						{
							$match[2] = str_replace('>', ' class="'.$scClass.'">', $match[2]);
						}

						$strBuffer = $match[1].$match[2].$match[3];
					}
				}

				if (!empty($GLOBALS['SIMPLECOLUMNS']['close']) && ($objElement->simple_columns_close) && (!$simpleColumnRowspan || $closeRowspan))
				{
					$strBuffer .= $GLOBALS['SIMPLECOLUMNS']['close'];
				}
			}
			else
			{
				$strBuffer = ($simpleColumnRowspan ? $simpleColumnBeHtml : $be_html) . '</div>' . $strBuffer;
			}
		}

		return $strBuffer;
	}

}
