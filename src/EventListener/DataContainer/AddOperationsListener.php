<?php
namespace LukasBableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Contao\NewsModel;
use Contao\PageModel;
use Contao\System;
use LukasBableck\ContaoInstantIndexingBundle\Client\Google;

#[AsHook('loadDataContainer')]
class AddOperationsListener extends Backend {
	public function __construct(private Google $googleClient) {
	}

	public function __invoke(string $table): void {
		if ('tl_page' !== $table && 'tl_news' !== $table && 'tl_calendar_events' !== $table && 'tl_faq' !== $table) {
			return;
		}

		$GLOBALS['TL_DCA'][$table]['list']['operations']['indexGoogle'] = [
			'label' => &$GLOBALS['TL_LANG']['MSC']['indexGoogle'],
			'href' => 'key=indexGoogle',
			'icon' => 'bundles/contaoinstantindexing/icons/google.svg',
		];

		if ('indexGoogle' === Input::get('key') && Input::get('id')) {
			$page = null;
			$rootPage = null;
			$model = null;
			switch ($table) {
				case 'tl_page':
					$page = PageModel::findWithDetails(Input::get('id'));
					break;
				case 'tl_news':
					$model = NewsModel::findByPk(Input::get('id'));
					break;
				case 'tl_calendar_events':
					$model = CalendarEventsModel::findByPk(Input::get('id'));
					break;
				case 'tl_faq':
					$model = FaqModel::findByPk(Input::get('id'));
			}

			if (null !== $model && null === $page) {
				$archive = $model->getRelated('pid');
				if (null !== $archive) {
					$page = $archive->getRelated('jumpTo')->loadDetails();
					$rootPage = PageModel::findByPk($page->rootId);
					if (null !== $page) {
						$url = $page->getAbsoluteUrl('/'.$model->alias);
					}
				}
			} elseif (null === $model && null !== $page) {
				$rootPage = PageModel::findByPk($page->rootId);
				$url = $page->getAbsoluteUrl();
			} else {
				return;
			}

			if (null === $rootPage || !$rootPage->googleServiceAccountJSON) {
				return;
			}

			$this->googleClient->publish($url, html_entity_decode($rootPage->googleServiceAccountJSON));

			$this->redirect($this->getReferer());
		}
	}
}
