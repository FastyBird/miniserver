<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers\Responses;

/**
 * Response interface
 */
interface ControllerResponse
{

	/**
	 * @return array<mixed>|null
	 */
	public function create(): array|null;

}
