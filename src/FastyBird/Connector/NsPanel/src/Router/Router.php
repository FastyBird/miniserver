<?php declare(strict_types = 1);

/**
 * Router.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Router
 * @since          1.0.0
 *
 * @date           10.07.23
 */

namespace FastyBird\Connector\NsPanel\Router;

use FastyBird\Connector\NsPanel\Controllers;
use FastyBird\Library\SlimRouter\Routing;

/**
 * Connector router configuration
 *
 * @package        FastyBird:NsPanelConnector!
 * @subpackage     Router
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class Router extends Routing\Router
{

	public const URL_GATEWAY_ID = 'gateway';

	public const URL_DEVICE_ID = 'device';

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
