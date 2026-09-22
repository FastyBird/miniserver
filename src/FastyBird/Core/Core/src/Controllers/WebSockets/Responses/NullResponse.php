<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Responses;

use Nette;

/**
 * Null response
 */
class NullResponse implements IResponse
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	public function create(): array|null
	{
		return null;
	}

	public function __toString(): string
	{
		return $this->create();
	}

}
