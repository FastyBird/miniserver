<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Router;

use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Http\Routing;

/**
 * Connector router configuration
 */
final class Router extends Routing\Router
{

	public const string URL_GATEWAY_ID = 'gateway';

	public const string URL_DEVICE_ID = 'device';

	/**
	 * @throws Exceptions\Runtime
	 */
	public function __construct(
		Controllers\DirectiveController $directiveController,
	)
	{
		parent::__construct();

		$this->post(
			'/do-directive/{' . self::URL_GATEWAY_ID . '}/{' . self::URL_DEVICE_ID . '}',
			[$directiveController, 'process'],
		);
	}

}
