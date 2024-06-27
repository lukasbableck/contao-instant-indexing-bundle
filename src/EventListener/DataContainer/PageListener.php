<?php
namespace Lukasbableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use Lukasbableck\ContaoInstantIndexingBundle\Client\Google;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\KernelEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class PageListener extends Backend{
	private static $queue = [];

	public function __construct(private Google $googleClient) {
	}

	#[AsEventListener(KernelEvents::TERMINATE)]
	public function onTerminate(KernelEvent $event): void {
		if (empty(self::$queue)) {
			return;
		}
		foreach (self::$queue as $item) {
			if (\array_key_exists('page', $item)) {
				$page = PageModel::findByPk($item['page']);
				$page->refresh();
				$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');
				$urlGenerator->reset();
				$pageUrl = $urlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL);
				if (\array_key_exists('delete', $item)) {
					$this->googleClient->publish($pageUrl, html_entity_decode($item['rootPage']->googleServiceAccountJSON, true));
				} else {
					$this->googleClient->publish($pageUrl, html_entity_decode($item['rootPage']->googleServiceAccountJSON));
				}
			} elseif (\array_key_exists('pageUrl', $item)) {
				if (\array_key_exists('delete', $item)) {
					$this->googleClient->publish($item['pageUrl'], html_entity_decode($item['rootPage']->googleServiceAccountJSON, true));
				} else {
					$this->googleClient->publish($item['pageUrl'], html_entity_decode($item['rootPage']->googleServiceAccountJSON));
				}
			}
		}
		self::$queue = [];
	}

	#[AsCallback(table: 'tl_page', target: 'select.buttons')]
	public function addIndexButton(array $arrButtons, DataContainer $dc): array {
		if (null !== Input::post('index_google') && 'tl_select' == Input::post('FORM_SUBMIT')) {
			$objSession = System::getContainer()->get('request_stack')->getSession();
			$session = $objSession->all();
			$ids = $session['CURRENT']['IDS'] ?? [];

			foreach ($ids as $id) {
				$page = PageModel::findWithDetails($id);
				if (null === $page) {
					continue;
				}
				$pageId = $page->id;
				$rootPage = PageModel::findByPk($page->rootId);
				if (!$rootPage->googleServiceAccountJSON) {
					continue;
				}
				self::$queue[] = ['page' => $pageId, 'rootPage' => $rootPage];
			}
			$this->redirect($this->getReferer());
		}
		$arrButtons['indexGoogle'] = '<button type="submit" name="index_google" id="index_google" class="tl_submit" accesskey="i">'.$GLOBALS['TL_LANG']['MSC']['indexGoogleSelected'].'</button> ';

		return $arrButtons;
	}

	#[AsCallback(table: 'tl_page', target: 'config.onsubmit', priority: -100)]
	public function onSubmit(DataContainer $dc): void {
		if ('regular' !== $dc->activeRecord->type) {
			return;
		}
		$newRecords = System::getContainer()->get('request_stack')->getSession()->getBag('contao_backend')->get('new_records');
		if (\is_array($newRecords) && \array_key_exists('tl_page', $newRecords) && \in_array($dc->activeRecord->id, $newRecords['tl_page'])) {
			$page = PageModel::findByPk($dc->activeRecord->id)->loadDetails();
			$pageId = $page->id;
			$rootPage = PageModel::findByPk($page->rootId);
			if (!$rootPage->googleServiceAccountJSON || !$rootPage->autoIndexGoogle) {
				return;
			}
			self::$queue[] = ['page' => $pageId, 'rootPage' => $rootPage];
		}
	}

	#[AsCallback(table: 'tl_page', target: 'config.ondelete')]
	public function onDelete(DataContainer $dc, int $undoId): void {
		if ('regular' !== $dc->activeRecord->type) {
			return;
		}
		$page = PageModel::findByPk($dc->activeRecord->id)->loadDetails();
		$urlGenerator = System::getContainer()->get('contao.routing.content_url_generator');
		$pageUrl = $urlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL);
		$rootPage = PageModel::findByPk($page->rootId);
		if (!$rootPage->googleServiceAccountJSON || !$rootPage->autoUnindexGoogle) {
			return;
		}
		self::$queue[] = ['pageUrl' => $pageUrl, 'rootPage' => $rootPage, 'delete' => true];
	}
}
