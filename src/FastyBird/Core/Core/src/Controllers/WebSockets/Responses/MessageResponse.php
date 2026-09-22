<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Responses;

use Nette;

/**
 * Simple data response only for own handled message
 */
class MessageResponse implements IResponse
{

	/**
	 * Implement nette smart magic
	 */
	use Nette\SmartObject;

	/**
	 * @param array<mixed> $data
	 */
	public function __construct(private array $data)
	{
	}

	public function create(): array|null
	{
		return $this->data;
	}

	/**
	 * @throws Nette\Utils\JsonException
	 */
	public function __toString(): string
	{
		return Nette\Utils\Json::encode($this->create());
	}

}
