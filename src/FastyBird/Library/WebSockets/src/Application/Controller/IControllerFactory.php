<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Application\Controller;

use FastyBird\Library\WebSockets\Exceptions;

/**
 * Responsible for creating a new instance of given controller
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Application
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IControllerFactory
{

	/**
	 * Generates and checks presenter class name
	 *
	 * @return string class name
	 *
	 * @throws Exceptions\InvalidController
	 */
	public function getControllerClass(string &$name): string;

	/**
	 * Creates new controller instance
	 */
	public function createController(string $name): IController;

}
