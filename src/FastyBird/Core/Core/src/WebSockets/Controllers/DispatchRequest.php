<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

/**
 * Controller request interface
 */
interface DispatchRequest
{

	/**
	 * Sets the controller name
	 */
	public function setControllerName(string $name): void;

	/**
	 * Retrieve the controller name
	 */
	public function getControllerName(): string;

	/**
	 * Sets variables provided to the controller
	 */
	public function setParameters(array $params): void;

	/**
	 * Returns all variables provided to the controller (usually via URL)
	 */
	public function getParameters(): array;

	/**
	 * Returns a parameter provided to the controller
	 */
	public function getParameter(string $key): mixed;

}
