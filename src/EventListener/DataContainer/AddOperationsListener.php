<?php
namespace LukasBableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;

#[AsHook('loadDataContainer')]
class AddOperationsListener {
	public function __invoke(string $table): void {
		if ('tl_page' !== $table && 'tl_news' !== $table && 'tl_calendar_events' !== $table && 'tl_faq' !== $table) {
			return;
		}

		$GLOBALS['TL_DCA'][$table]['list']['operations']['indexGoogle'] = [
			'label' => &$GLOBALS['TL_LANG']['MSC']['indexGoogle'],
			'href' => 'key=indexGoogle',
			'icon' => 'bundles/contaoinstantindexing/icons/google.svg',
		];
	}
}
