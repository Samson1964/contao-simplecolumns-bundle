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
		'disabled'                    => ($GLOBALS['TL_CONFIG']['simpleColumnsBoxSizing'] != 'border-box')
	),
	'sql'                             => "char(1) NOT NULL default ''"
);


foreach ($GLOBALS['TL_DCA']['tl_content']['palettes'] as $key => $palette)
{
	$strPalette = '{simple_columns_legend},simple_columns,simple_columns_rowspan,simple_columns_close,simple_columns_wrapper,simple_columns_autoheight,simple_columns_border';

	if (!is_array($palette))
	{
		if (strpos($palette, '{expert_legend:hide}') !== FALSE)
		{
			$GLOBALS['TL_DCA']['tl_content']['palettes'][$key] = str_replace('{expert_legend:hide}', $strPalette.';{expert_legend:hide}', $palette);
		}
		elseif (strpos($palette, '{protected_legend:hide}') !== FALSE)
		{
			$GLOBALS['TL_DCA']['tl_content']['palettes'][$key] = str_replace('{protected_legend:hide}', $strPalette.';{protected_legend:hide}', $palette);
		}
		else
		{
			$GLOBALS['TL_DCA']['tl_content']['palettes'][$key] .= ';'.$strPalette;
		}
	}
}


class simpleColumns extends Backend
{
	public function onLoadCallback($dc)
	{
		$objContent = $this->Database->prepare('
			SELECT
				`id`,`simple_columns`,`simple_columns_rowspan`
			FROM
				`tl_content`
			WHERE
				`pid` = (SELECT `pid` FROM `tl_content` WHERE `id`=?) AND `invisible`=""
			ORDER BY
				`sorting`
		')->execute($dc->id);

		$rowspan = 0;

		while ($objContent->next())
		{
			if ($objContent->id == $dc->id)
			{
				break;
			}

			if ($rowspan > 0)
			{
				$rowspan--;
			}
			elseif ($objContent->simple_columns != '' && $objContent->simple_columns_rowspan > 1)
			{
				$rowspan = $objContent->simple_columns_rowspan-1;
			}
		}

		if ($rowspan > 0)
		{
			$GLOBALS['TL_LANG']['tl_content']['simple_columns_legend'] = $GLOBALS['TL_LANG']['tl_content']['simple_columns_legend_ignore'];
		}
	}

	public function disabledField($dc)
	{
		return '<p>Disabled</p>';
	}

	public function hiddenField($dc)
	{
		return '';
	}

}
