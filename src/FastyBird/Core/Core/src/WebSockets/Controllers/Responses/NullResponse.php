<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers\Responses;

use Override;

/**
 * Null response
 */
final class NullResponse implements ControllerResponse
{

	#[Override]
	public function create(): array|null
	{
		return null;
	}

	#[Override]
	public function __toString(): string
	{
		return $this->create();
	}

}
