<?php
namespace Lukasbableck\ContaoInstantIndexingBundle\Controller;

use Contao\CalendarEventsModel;
use Contao\CoreBundle\Controller\AbstractController;
use Contao\CoreBundle\Routing\ContentUrlGenerator;
use Contao\FaqModel;
use Contao\NewsModel;
use Contao\PageModel;
use Lukasbableck\ContaoInstantIndexingBundle\Client\Google;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/%contao.backend.route_prefix%/instant-indexing/{table}/{id}', name: 'contao_instant_indexing', defaults: ['_scope' => 'backend'], requirements: ['id' => '\d+'], methods: ['GET'], )]
class GoogleIndexingController extends AbstractController {
	public function __construct(
		private Google $googleClient,
		private ContentUrlGenerator $contentUrlGenerator,
		private TranslatorInterface $translator,
	) {
	}

	public function __invoke(string $table, int $id, Request $request): Response {
		$this->initializeContaoFramework();

		$page = null;
		$rootPage = null;
		$model = null;
		$url = null;

		switch ($table) {
			case 'tl_page':
				$page = PageModel::findWithDetails($id);
				break;
			case 'tl_news':
				$model = NewsModel::findByPk($id);
				break;
			case 'tl_calendar_events':
				$model = CalendarEventsModel::findByPk($id);
				break;
			case 'tl_faq':
				$model = FaqModel::findByPk($id);
				break;
			default:
				throw $this->createNotFoundException();
		}

		if (null !== $model) {
			$archive = $model->getRelated('pid');
			/** @var PageModel|null $jumpTo */
			$jumpTo = $archive?->getRelated('jumpTo');
			$page = $jumpTo?->loadDetails();

			if (null !== $page) {
				$rootPage = PageModel::findByPk($page->rootId);
				$url = $this->contentUrlGenerator->generate($page, ['parameters' => '/'.$model->alias], UrlGeneratorInterface::ABSOLUTE_URL);
			}
		} elseif (null !== $page) {
			$rootPage = PageModel::findByPk($page->rootId);
			$url = $this->contentUrlGenerator->generate($page, [], UrlGeneratorInterface::ABSOLUTE_URL);
		}

		if (null === $page || 'regular' !== $page->type || null === $rootPage || !$rootPage->googleServiceAccountJSON || null === $url) {
			return $this->redirectBack($request);
		}

		$this->googleClient->publish($url, html_entity_decode($rootPage->googleServiceAccountJSON));

		if ($request->hasSession()) {
			$session = $request->getSession();

			if ($session instanceof FlashBagAwareSessionInterface) {
				$session->getFlashBag()->add(
					'contao.BE.confirm',
					\sprintf($this->translator->trans('instantIndexing.urlSubmitted', [], 'contao_instant_indexing'), $url),
				);
			}
		}

		return $this->redirectBack($request);
	}

	private function redirectBack(Request $request): RedirectResponse {
		$returnUrl = $request->query->get('return_url');

		if (!\is_string($returnUrl) || !str_starts_with($returnUrl, '/') || str_starts_with($returnUrl, '//')) {
			$returnUrl = $this->generateUrl('contao_backend');
		}

		return new RedirectResponse($returnUrl);
	}
}
