<?php
namespace Lukasbableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Contao\Input;
use Contao\PageModel;
use Lukasbableck\ContaoInstantIndexingBundle\Client\Google;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsHook('loadDataContainer')]
class AddOperationsListener extends Backend {
	public function __construct(
		private Google $googleClient,
		private RequestStack $requestStack,
		private TranslatorInterface $translator,
	) {
	}

	public function __invoke(string $table): void {
		if ('tl_page' !== $table && 'tl_news' !== $table && 'tl_calendar_events' !== $table && 'tl_faq' !== $table) {
			return;
		}

		$GLOBALS['TL_DCA'][$table]['list']['operations']['indexGoogle'] = [
			'label' => &$GLOBALS['TL_LANG']['MSC']['indexGoogle'],
			'href' => 'act=indexGoogle',
			'icon' => 'bundles/contaoinstantindexing/icons/google.svg',
			'button_callback' => ['\Lukasbableck\ContaoInstantIndexingBundle\EventListener\DataContainer\ButtonListener', 'addIndexGoogleButton'],
		];

		if ('indexGoogle' === Input::get('act') && Input::get('id')) {
			$page = null;
			$rootPage = null;
			$model = null;
			switch ($table) {
				case 'tl_page':
					$page = PageModel::findWithDetails(Input::get('id'));
					break;
				case 'tl_news':
					$model = \Contao\NewsModel::findByPk(Input::get('id'));
					break;
				case 'tl_calendar_events':
					$model = \Contao\CalendarEventsModel::findByPk(Input::get('id'));
					break;
				case 'tl_faq':
					$model = \Contao\FaqModel::findByPk(Input::get('id'));
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

			if ('regular' !== $page->type) {
				return;
			}

			if (null === $rootPage || !$rootPage->googleServiceAccountJSON) {
				return;
			}

			$this->googleClient->publish($url, html_entity_decode($rootPage->googleServiceAccountJSON));

			$session = $this->requestStack->getMainRequest()->getSession();
			if ($session instanceof Session) {
				$session->getFlashBag()->add('contao.BE.confirm', \sprintf($this->translator->trans('instantIndexing.urlSubmitted', [], 'contao_instant_indexing'), $url));
			}
			$this->redirect($this->getReferer());
		}
	}
}
