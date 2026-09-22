<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Responses;

use Nette;
use Override;

/**
 * Null response
 */
final class NullResponse implements IResponse
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

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
