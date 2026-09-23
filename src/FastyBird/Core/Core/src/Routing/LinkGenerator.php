<?php declare(strict_types = 1);

namespace FastyBird\Core\Routing;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Controllers\WebSockets\Controller;
use FastyBird\Core\Exceptions;
use ReflectionException;
use ReflectionParameter;
use function array_key_exists;
use function assert;
use function http_build_query;
use function is_subclass_of;
use function method_exists;
use function preg_match;
use function sprintf;
use function urldecode;

/**
 * WebSockets connection link generator
 */
final class LinkGenerator
{

	public function __construct(
		private IWampRouter $router,
		private Controller\IControllerFactory|null $controllerFactory = null,
	)
	{
	}

	/**
	 * Generates URL to controller
	 *
	 * @param string $destination in format "[[[module:]controller:]action] [#fragment]"
	 *
	 * @throws Exceptions\InvalidLink
	 * @throws ReflectionException
	 */
	public function link(string $destination, array $params = []): string
	{
		if (!preg_match('~^([\w:]+):(\w*+)(#.*)?()\z~', $destination, $m)) {
			throw new Exceptions\InvalidLink(sprintf('Invalid link destination "%s".', $destination));
		}

		[, $controller, $action, $frag] = $m;

		try {
			$class = $this->controllerFactory ? $this->controllerFactory->getControllerClass($controller) : null;

		} catch (Exceptions\InvalidController $ex) {
			throw new Exceptions\InvalidLink($ex->getMessage(), 0, $ex);
		}

		if (is_subclass_of($class, Controller\Controller::class)) {
			if (method_exists($class, $method = $class::formatActionMethod($action))) {
				$missing = [];

				Controller\Controller::argsToParams($class, $method, $params, [], $missing);

				if ($missing !== []) {
					$rp = $missing[0];
					assert($rp instanceof ReflectionParameter);

					throw new Exceptions\InvalidLink(
						sprintf(
							'Missing parameter $%s required by %s::%s()',
							$rp->getName(),
							$rp->getDeclaringClass()->getName(),
							$rp->getDeclaringFunction()->getName(),
						),
					);
				}
			} elseif (array_key_exists(0, $params)) {
				throw new Exceptions\InvalidLink(
					sprintf(
						'Unable to pass parameters to action "%s:%s", missing corresponding method.',
						$controller,
						$action,
					),
				);
			}
		}

		if ($action !== '') {
			$params[Controller\Controller::ACTION_KEY] = $action;
		}

		$url = $this->router->constructUrl(new Application\Request($controller, $params));

		if ($url === null) {
			unset($params[Controller\Controller::ACTION_KEY]);

			$params = urldecode(http_build_query($params, '', ', '));

			throw new Exceptions\InvalidLink(sprintf('No route for %s(%s)', $destination, $params));
		}

		return $url . $frag;
	}

}
