<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Handshake;

use Nette\Http;
use Override;
use function func_num_args;

/**
 * HTTP request
 */
final class Request extends Http\Request implements IRequest
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
		callable|null $rawBodyCallback = null,
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

	#[Override]
	public function setUrl(Http\UrlScript $url): void
	{
		$this->url = $url;
	}

	#[Override]
	public function getUrl(): Http\UrlScript
	{
		return clone $this->url;
	}

	#[Override]
	public function getQuery(string|null $key = null): mixed
	{
		return func_num_args() === 0 ? $this->url->getQueryParameters() : $this->url->getQueryParameter($key);
	}

	#[Override]
	public function isSecured(): bool
	{
		return $this->url->getScheme() === 'wss';
	}

	#[Override]
	public function setProtocolVersion(float $version): void
	{
		$this->protocolVersion = $version;
	}

	#[Override]
	public function getProtocolVersion(): float|null
	{
		return $this->protocolVersion;
	}

}
