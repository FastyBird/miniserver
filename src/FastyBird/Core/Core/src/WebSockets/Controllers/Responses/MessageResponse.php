<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Controllers\Responses;

use Nette;
use Override;

/**
 * Simple data response only for own handled message
 */
final class MessageResponse implements ControllerResponse
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
