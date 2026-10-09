<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

/**
 * Controller request
 */
final class Request
{

	/**
	 * The controller name
	 */
	public string $controllerName;

	/**
	 * Variables provided to the controller (usually via URL)
	 *
	 * @var array<mixed>
	 */
	public array $parameters;

	/**
	 * @param string $name  fully qualified controller name (module:module:controller)
	 * @param array $params variables provided to the controller usually via URL
	 */
	public function __construct(string $name, array $params = [])
	{
		$this->controllerName = $name;
		$this->parameters = $params;
	}

	/**
	 * Returns a parameter provided to the controller
	 */
	public function getParameter(string $key): mixed
	{
		return $this->parameters[$key] ?? null;
	}

}
