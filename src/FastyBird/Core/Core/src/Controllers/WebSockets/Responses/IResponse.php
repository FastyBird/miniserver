<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Responses;

/**
 * Response interface
 */
interface IResponse
{

	/**
	 * @return array<mixed>|null
	 */
	public function create(): array|null;

}
