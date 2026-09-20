<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Controller;

use FastyBird\Core\Exceptions\WebSockets as Exceptions;
use Nette;
use Nette\DI;
use Nette\Utils;
use ReflectionClass;
use ReflectionException;
use function array_keys;
use function array_shift;
use function assert;
use function call_user_func_array;
use function class_exists;
use function count;
use function explode;
use function implode;
use function is_array;
use function is_string;
use function preg_match;
use function preg_replace;
use function sprintf;
use function str_replace;
use function trigger_error;
use const E_USER_WARNING;

/**
 * Default controller loader
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ControllerFactory implements IControllerFactory
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/** @var array<array> of module => splited mask */
	private array $mapping = [
		'*' => ['', '*Module\\', '*Controller'],
		'IPubWebSockets' => ['IPubWebSocketsModule\\', '*\\', '*Controller'],
	];

	private array $cache = [];

	/** @var callable */
	private $factory;

	private DI\Container $container;

	public function __construct(Nette\DI\Container $container, callable|null $factory = null)
	{
		$this->container = $container;

		$this->factory = $factory ?? function (string $class) {
			$services = array_keys($this->container->findByTag('ipub.websockets.controller'), $class, true);

			if (count($services) > 1) {
				throw new Exceptions\InvalidController(
					sprintf('Multiple services of type "%s" found: %s.', $class, implode(', ', $services)),
				);
			} elseif ($services === []) {
				$controller = $this->container->createInstance($class);
				assert($controller instanceof IController);

				$this->container->callInjects($controller);

				return $controller;
			}

			return $this->container->createService($services[0]);
		};
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidController
	 * @throws ReflectionException
	 */
	public function createController(string $name): IController
	{
		return call_user_func_array($this->factory, [$this->getControllerClass($name)]);
	}

	/**
	 * Generates and checks controller class name
	 *
	 * @return string class name
	 *
	 * @throws Exceptions\InvalidController
	 * @throws ReflectionException
	 */
	public function getControllerClass(string &$name): string
	{
		if (isset($this->cache[$name])) {
			return $this->cache[$name];
		}

		if (!Utils\Strings::match($name, '#^[a-zA-Z\x7f-\xff][a-zA-Z0-9\x7f-\xff:]*\z#')) {
			throw new Exceptions\InvalidController(
				sprintf('Controller name must be alphanumeric string, "%s" is invalid.', $name),
			);
		}

		$class = $this->formatControllerClass($name);

		if (!class_exists($class)) {
			throw new Exceptions\InvalidController(
				sprintf('Cannot load controller "%s", class "%s" was not found.', $name, $class),
			);
		}

		$reflection = new ReflectionClass($class);
		$class = $reflection->getName();

		if (!$reflection->implementsInterface(IController::class)) {
			throw new Exceptions\InvalidController(
				sprintf(
					'Cannot load controller "%s", class "%s" is not FastyBird\\Core\\Controllers\\WebSockets\\Controller\\IController implementor.',
					$name,
					$class,
				),
			);
		} elseif ($reflection->isAbstract()) {
			throw new Exceptions\InvalidController(
				sprintf('Cannot load controller "%s", class "%s" is abstract.', $name, $class),
			);
		}

		$this->cache[$name] = $class;

		if ($name !== ($realName = $this->unFormatControllerClass($class))) {
			trigger_error(
				sprintf('Case mismatch on controller name "%s", correct name is "%s".', $name, $realName),
				E_USER_WARNING,
			);

			$name = $realName;
		}

		return $class;
	}

	/**
	 * Sets mapping as pairs [module => mask]
	 *
	 * @throws Exceptions\InvalidState
	 */
	public function setMapping(array $mapping): void
	{
		foreach ($mapping as $module => $mask) {
			if (is_string($mask)) {
				if (!preg_match('#^\\\\?([\w\\\\]*\\\\)?(\w*\*\w*?\\\\)?([\w\\\\]*\*\w*)\z#', $mask, $m)) {
					throw new Exceptions\InvalidState(sprintf('Invalid mapping mask "%s".', $mask));
				}

				$this->mapping[$module] = [$m[1], $m[2] !== '' ? $m[2] : '*Module\\', $m[3]];

			} elseif (is_array($mask) && count($mask) === 3) {
				$this->mapping[$module] = [$mask[0] ? $mask[0] . '\\' : '', $mask[1] . '\\', $mask[2]];

			} else {
				throw new Exceptions\InvalidState(sprintf('Invalid mapping mask for module "%s".', $module));
			}
		}
	}

	/**
	 * Formats controller class name from its name
	 *
	 * @internal
	 */
	public function formatControllerClass(string $controller): string
	{
		$parts = explode(':', $controller);
		$mapping = isset($parts[1], $this->mapping[$parts[0]])
			? $this->mapping[array_shift($parts)]
			: $this->mapping['*'];

		while ($part = array_shift($parts)) {
			$mapping[0] .= str_replace('*', $part, $mapping[$parts ? 1 : 2]);
		}

		return $mapping[0];
	}

	/**
	 * Formats controller name from class name
	 *
	 * @internal
	 */
	public function unFormatControllerClass(string $class): string|bool
	{
		foreach ($this->mapping as $module => $mapping) {
			$mapping = str_replace(['\\', '*'], ['\\\\', '(\w+)'], $mapping);

			if (preg_match(
				sprintf('#^\\\\?%s((?:%s)*)%s\\z#i', $mapping[0], $mapping[1], $mapping[2]),
				$class,
				$matches,
			)) {
				return ($module === '*' ? '' : $module . ':')
					. preg_replace(sprintf('#%s#iA', $mapping[1]), '$1:', $matches[1]) . $matches[3];
			}
		}

		return false;
	}

}
