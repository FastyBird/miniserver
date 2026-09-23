<?php declare(strict_types = 1);

namespace FastyBird\Core\Controllers\WebSockets\Responses;

use Nette;
use Override;

/**
 * Simple data response only for own handled message
 */
final class MessageResponse implements IResponse
{

	/**
	 * @param array<mixed> $data
	 */
	public function __construct(private array $data)
	{
	}

	#[Override]
	public function create(): array|null
	{
		return $this->data;
	}

	/**
	 * @throws Nette\Utils\JsonException
	 */
	#[Override]
	public function __toString(): string
	{
		return Nette\Utils\Json::encode($this->create());
	}

}
