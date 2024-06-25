<?php
namespace LukasBableck\ContaoInstantIndexingBundle\ContaoManager;

use Contao\CoreBundle\ContaoCoreBundle;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;
use LukasBableck\ContaoInstantIndexingBundle\ContaoInstantIndexingBundle;

class Plugin implements BundlePluginInterface {
	public function getBundles(ParserInterface $parser): array {
		return [BundleConfig::create(ContaoInstantIndexingBundle::class)->setLoadAfter([ContaoCoreBundle::class])];
	}
}
