<?php
namespace Lukasbableck\ContaoInstantIndexingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ContaoInstantIndexingBundle extends Bundle {
	public function getPath(): string {
		return \dirname(__DIR__);
	}
}
