<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Http;

use Nette\Http;
use function func_num_args;

/**
 * HTTP request
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Http
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class Request extends Http\Request implements IRequest
{

	private float $protocolVersion;

	public function __construct(
		private Http\UrlScript $url,
		array $post = [],
		array $files = [],
		array $cookies = [],
		array $headers = [],
		string $method = 'GET',
		string|null $remoteAddress = null,
		string|null $remoteHost = null,
		null $rawBodyCallback = null,
	)
	{
		parent::__construct(
			$url,
			$post,
			$files,
			$cookies,
			$headers,
			$method,
			$remoteAddress,
			$remoteHost,
			$rawBodyCallback,
		);
	}

	public function setUrl(Http\UrlScript $url): void
	{
		$this->url = $url;
	}

	public function getUrl(): Http\UrlScript
	{
		return clone $this->url;
	}

	public function getQuery(string|null $key = null): mixed
	{
		return func_num_args() === 0 ? $this->url->getQueryParameters() : $this->url->getQueryParameter($key);
	}

	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'wss';
	}

	public function setProtocolVersion(float $version): void
	{
		$this->protocolVersion = $version;
	}

	public function getProtocolVersion(): float|null
	{
		return $this->protocolVersion;
	}

}
