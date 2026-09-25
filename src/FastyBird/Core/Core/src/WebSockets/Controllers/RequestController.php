<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers;

/**
 * WebSockets controller interface
 */
interface RequestController
{

	public function run(Request $request): Responses\ControllerResponse;

	public function getName(): string;

}
