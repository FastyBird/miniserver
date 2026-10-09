<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

/**
 * Controller request interface
 */
interface DispatchRequest
{

	/**
	 * The controller name
	 */
	// phpcs:ignore Internal.ParseError.InterfaceHasMemberVar, Generic.Formatting.DisallowMultipleStatements.SameLine -- PHP_CodeSniffer 3 does not tokenize property hooks
	public string $controllerName { get; set; }

	/**
	 * Variables provided to the controller (usually via URL)
	 *
	 * @var array<mixed>
	 */
	// phpcs:ignore Internal.ParseError.InterfaceHasMemberVar, Generic.Formatting.DisallowMultipleStatements.SameLine -- PHP_CodeSniffer 3 does not tokenize property hooks
	public array $parameters { get; set; }

	/**
	 * Returns a parameter provided to the controller
	 */
	public function getParameter(string $key): mixed;

}
