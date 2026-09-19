<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Application\Responses;

use Nette;

/**
 * Null response
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Responses
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
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
