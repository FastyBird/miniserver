<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Middleware;

use Closure;
use FastyBird\Core\Http\Exceptions;
use FastyBird\Core\Http\Server;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use React\Http\Message\Response;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function pathinfo;
use function realpath;
use function strtolower;
use const PATHINFO_EXTENSION;

/**
 * Public static files middleware
 */
final class StaticFiles
{

	private string|null $publicRoot;

	public function __construct(string|null $publicRoot, private readonly bool $enabled = false)
	{
		$publicRoot = $publicRoot !== null ? realpath($publicRoot) : null;

		$this->publicRoot = $publicRoot === false ? null : $publicRoot;
	}

	private function getMimeType(string $file): string
	{
		$extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

		if (isset(Server\MimeTypesList::MIMES[$extension])) {
			return Server\MimeTypesList::MIMES[$extension][0];
		}

		return 'text/plain';
	}

	/**
	 * @param Closure(ServerRequestInterface $request): ResponseInterface $next
	 *
	 * @throws InvalidArgumentException
	 * @throws Exceptions\FileNotFound
	 */
	public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
	{
		if ($this->publicRoot === null || !$this->enabled) {
			return $next($request);
		}

		$files = [
			$request->getUri()->getPath(),
			$request->getUri()->getPath() . '/index.html',
			$request->getUri()->getPath() . '/index.htm',
		];

		foreach ($files as $filePath) {
			$file = realpath($this->publicRoot . $filePath);

			if ($file !== false && file_exists($file) && !is_dir($file)) {
				$fileContents = file_get_contents($file);

				if ($fileContents === false) {
					throw new Exceptions\FileNotFound('Content of requested file could not be loaded');
				}

				return new Response(200, ['Content-Type' => $this->getMimeType($file)], $fileContents);
			}
		}

		return $next($request);
	}

}
