<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Application\Responses;

use Nette;

/**
 * Simple data response only for own handled message
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Responses
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @author         Vít Ledvinka, frosty22 <ledvinka.vit@gmail.com>
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
