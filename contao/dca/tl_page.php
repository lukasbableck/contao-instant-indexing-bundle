<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

$GLOBALS['TL_DCA']['tl_page']['list']['operations']['indexGoogle'] = [
	'label' => &$GLOBALS['TL_LANG']['MSC']['indexGoogle'],
	'href' => 'key=indexGoogle',
	'icon' => 'bundles/contaoinstantindexing/icons/google.svg',
];

$GLOBALS['TL_DCA']['tl_page']['fields']['googleServiceAccountJSON'] = [
	'exclude' => true,
	'inputType' => 'textarea',
	'eval' => ['tl_class' => 'long clr', 'rte' => 'ace|json'],
	'sql' => 'text NULL',
];
$GLOBALS['TL_DCA']['tl_page']['fields']['autoIndexGoogle'] = [
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => ['tl_class' => 'w50 clr'],
	'sql' => "char(1) NOT NULL default ''",
];
$GLOBALS['TL_DCA']['tl_page']['fields']['autoUnindexGoogle'] = [
	'exclude' => true,
	'inputType' => 'checkbox',
	'eval' => ['tl_class' => 'w50'],
	'sql' => "char(1) NOT NULL default ''",
];

PaletteManipulator::create()
	->addLegend('instant_index_legend', 'website_legend', PaletteManipulator::POSITION_AFTER)
	->addField('googleServiceAccountJSON', 'instant_index_legend', PaletteManipulator::POSITION_APPEND)
	->addField('autoIndexGoogle', 'instant_index_legend', PaletteManipulator::POSITION_APPEND)
	->addField('autoUnindexGoogle', 'instant_index_legend', PaletteManipulator::POSITION_APPEND)
	->applyToPalette('root', 'tl_page')
	->applyToPalette('rootfallback', 'tl_page')
;
