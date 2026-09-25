<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Handshake;

use Nette\Http;

/**
 * HTTP request interface
 */
interface IRequest extends Http\IRequest
{

	public function setUrl(Http\UrlScript $url): void;

	public function setProtocolVersion(float $version): void;

	public function getProtocolVersion(): float|null;

}
