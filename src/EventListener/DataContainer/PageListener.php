<?php
namespace LukasBableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DependencyInjection\Attribute\AsCallback;
use Contao\DataContainer;
use Contao\Input;
use Contao\PageModel;
use Contao\System;
use LukasBableck\ContaoInstantIndexingBundle\Client\Google;

class PageListener {
	public function __construct(private Google $googleClient) {
	}

	#[AsCallback(table: 'tl_page', target: 'select.buttons')]
	public function addIndexButton(array $arrButtons, DataContainer $dc): array {
		if (null !== Input::post('index_google') && 'tl_select' == Input::post('FORM_SUBMIT')) {
			$router = System::getContainer()->get('router');
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

				$dc->id = $id;
				$dc->activeRecord = $objPage;

				$pageUrl = $objPage->getAbsoluteUrl();
				$this->googleClient->index($pageUrl, html_entity_decode($rootPage->googleServiceAccountJSON));
			}
		}

		$arrButtons['indexGoogle'] = '<button type="submit" name="index_google" id="index_google" class="tl_submit" accesskey="i">'.$GLOBALS['TL_LANG']['MSC']['indexGoogleSelected'].'</button> ';

		return $arrButtons;
	}

	#[AsCallback(table: 'tl_page', target: 'config.onsubmit')]
	public function onSubmit(DataContainer $dc): void {
		if ('regular' !== $dc->activeRecord->type) {
			return;
		}

		$rootPage = $dc->activeRecord->loadDetails()->rootId;
		$rootPageModel = PageModel::findByPk($rootPage);

		if (!$rootPageModel->googleServiceAccountJSON) {
			return;
		}
	}
}
