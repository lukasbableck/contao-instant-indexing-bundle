<?php
namespace Lukasbableck\ContaoInstantIndexingBundle\EventListener\DataContainer;

use Contao\CoreBundle\DataContainer\DataContainerOperation;
use Contao\CoreBundle\DependencyInjection\Attribute\AsHook;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[AsHook('loadDataContainer')]
class AddOperationsListener {
	public function __construct(
		private UrlGeneratorInterface $urlGenerator,
		private RequestStack $requestStack,
	) {
	}

	public function __invoke(string $table): void {
		if ('tl_page' !== $table && 'tl_news' !== $table && 'tl_calendar_events' !== $table && 'tl_faq' !== $table) {
			return;
		}

		$GLOBALS['TL_DCA'][$table]['list']['operations']['indexGoogle'] = [
			'label' => &$GLOBALS['TL_LANG']['MSC']['indexGoogle'],
			'icon' => 'bundles/contaoinstantindexing/icons/google.svg',
			'button_callback' => [$this, 'addIndexGoogleButton'],
		];
	}

	public function addIndexGoogleButton(DataContainerOperation $operation): void {
		$row = $operation->getRecord();
		$table = $operation->getDataContainer()->table;

		if (null === $row || !isset($row['id']) || ('tl_page' === $table && 'regular' !== ($row['type'] ?? null))) {
			$operation->hide();

			return;
		}

		$operation->setUrl($this->urlGenerator->generate('contao_instant_indexing', [
			'table' => $table,
			'id' => $row['id'],
			'return_url' => $this->requestStack->getCurrentRequest()?->getRequestUri(),
		]));
	}
}
