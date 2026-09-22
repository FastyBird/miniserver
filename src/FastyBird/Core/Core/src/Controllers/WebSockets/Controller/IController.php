<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Controller;

use FastyBird\Core\Controllers\WebSockets as Application;
use FastyBird\Core\Controllers\WebSockets\Responses;

/**
 * WebSockets controller interface
 */
interface IController
{

	public function run(Application\Request $request): Responses\IResponse;

	public function getName(): string;

}
