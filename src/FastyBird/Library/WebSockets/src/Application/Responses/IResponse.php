<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Application\Responses;

/**
 * Response interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Responses
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 * @author         Vít Ledvinka, frosty22 <ledvinka.vit@gmail.com>
 */
interface IResponse
{

	/**
	 * @return array<mixed>|null
	 */
	public function create(): array|null;

}
