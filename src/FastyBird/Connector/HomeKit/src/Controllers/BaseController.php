<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Controllers;

use FastyBird\Connector\HomeKit;
use Nette;

/**
 * API base controller
 */
abstract class BaseController
{

	use Nette\SmartObject;

	protected HomeKit\Logger $logger;

	public function setLogger(HomeKit\Logger $logger): void
	{
		$this->logger = $logger;
	}

}
