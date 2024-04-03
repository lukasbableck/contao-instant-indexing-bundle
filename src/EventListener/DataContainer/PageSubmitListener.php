<?php

namespace LukasBableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;

class PageSubmitListener {
	#[AsCallback(table: 'tl_page', target: 'config.onsubmit')]
	public function onSubmit(DataContainer $dc): void {
	}
}
