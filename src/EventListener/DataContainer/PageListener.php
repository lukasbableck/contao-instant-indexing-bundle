<?php
namespace LukasBableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\Backend;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use LukasBableck\ContaoInstantIndexingBundle\Client\Google;
use Symfony\Component\HttpFoundation\RequestStack;

class PageListener extends Backend {
	public function __construct(private Google $googleClient, private RequestStack $requestStack) {
	}

	#[AsCallback(table: 'tl_page', target: 'select.buttons')]
	public function addIndexButton(array $arrButtons, DataContainer $dc): array {
		if (null !== Input::post('index_google') && 'tl_select' == Input::post('FORM_SUBMIT')) {
			$objSession = System::getContainer()->get('request_stack')->getSession();
			$session = $objSession->all();
			$ids = $session['CURRENT']['IDS'] ?? [];

			foreach ($ids as $id) {
				$objPage = PageModel::findWithDetails($id);
				if (null === $objPage) {
					continue;
				}
				$rootPage = PageModel::findByPk($objPage->rootId);
				if (!$rootPage->googleServiceAccountJSON) {
					continue;
				}
				$pageUrl = $objPage->getAbsoluteUrl();
				$this->googleClient->publish($pageUrl, html_entity_decode($rootPage->googleServiceAccountJSON));
			}
			$this->redirect($this->getReferer());
		}
		$arrButtons['indexGoogle'] = '<button type="submit" name="index_google" id="index_google" class="tl_submit" accesskey="i">'.$GLOBALS['TL_LANG']['MSC']['indexGoogleSelected'].'</button> ';

		return $arrButtons;
	}

	#[AsCallback(table: 'tl_page', target: 'config.onsubmit')]
	public function onSubmit(DataContainer $dc): void {
		if ('regular' !== $dc->activeRecord->type) {
			return;
		}
		$newRecords = System::getContainer()->get('request_stack')->getSession()->getBag("contao_backend")->get('new_records');
		if(is_array($newRecords) && array_key_exists("tl_page", $newRecords) && in_array($dc->activeRecord->id, $newRecords["tl_page"])){
			$page = PageModel::findByPk($dc->activeRecord->id)->loadDetails();
			$rootPage = PageModel::findByPk($page->rootId);
			if (!$rootPage->googleServiceAccountJSON || !$rootPage->autoIndexGoogle) {
				return;
			}
			$pageUrl = $page->getAbsoluteUrl();
			$this->googleClient->publish($pageUrl, html_entity_decode($rootPage->googleServiceAccountJSON));
		}
	}

	#[AsCallback(table: 'tl_page', target: 'config.ondelete')]
	public function onDelete(DataContainer $dc, int $undoId): void {
		if ('regular' !== $dc->activeRecord->type) {
			return;
		}
		$page = PageModel::findByPk($dc->activeRecord->id)->loadDetails();
		$rootPage = PageModel::findByPk($page->rootId);
		if (!$rootPage->googleServiceAccountJSON || !$rootPage->autoUnindexGoogle) {
			return;
		}
		$pageUrl = $page->getAbsoluteUrl();
		$this->googleClient->publish($pageUrl, html_entity_decode($rootPage->googleServiceAccountJSON), true);
	}
}
