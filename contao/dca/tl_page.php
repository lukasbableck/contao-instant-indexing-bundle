<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['fields']['googleServiceAccountJSON'] = [
	'exclude' => true,
	'inputType' => 'textarea',
	'eval' => ['tl_class' => 'long clr', 'rte' => 'ace|json'],
	'sql' => 'text NULL',
];

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['indexGoogle'] = [
	'label' => &$GLOBALS['TL_LANG']['MSC']['indexGoogle'],
	'href' => 'key=indexGoogle',
	'icon' => 'google.svg',
];

PaletteManipulator::create()
	->addField('googleServiceAccountJSON', 'website_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('root', 'tl_page')
	->applyToPalette('rootfallback', 'tl_page')
;
