<?php
namespace Lukasbableck\ContaoInstantIndexingBundle\ContaoManager;

use Contao\CalendarBundle\ContaoCalendarBundle;
use Contao\CoreBundle\ContaoCoreBundle;
use Contao\FaqBundle\ContaoFaqBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use Contao\NewsBundle\ContaoNewsBundle;
use Lukasbableck\ContaoInstantIndexingBundle\ContaoInstantIndexingBundle;

class Plugin implements BundlePluginInterface {
	public function getBundles(ParserInterface $parser): array {
		return [BundleConfig::create(ContaoInstantIndexingBundle::class)->setLoadAfter([
			ContaoCoreBundle::class,
			ContaoCalendarBundle::class,
			ContaoFaqBundle::class,
			ContaoNewsBundle::class,
		])];
	}
}
