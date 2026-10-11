<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Controllers;

use FastyBird\Connector\HomeKit;

/**
 * API base controller
 */
abstract class BaseController
{

	protected HomeKit\Logger $logger;

	public function setLogger(HomeKit\Logger $logger): void
	{
		$this->logger = $logger;
	}

}
