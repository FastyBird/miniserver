<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

use Nette\Http;

/**
 * HTTP request interface
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
interface IRequest extends Http\IRequest
{

	public function setUrl(Http\UrlScript $url): void;

	public function setProtocolVersion(float $version): void;

	public function getProtocolVersion(): float|null;

}
