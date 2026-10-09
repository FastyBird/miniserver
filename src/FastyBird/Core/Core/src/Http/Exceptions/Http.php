<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Exceptions;

use Exception;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class Http extends Exception
{

	public protected(set) string $title = '';

	public protected(set) string $description = '';

	public function __construct(
		protected ServerRequestInterface $request,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

	public function getRequest(): ServerRequestInterface
	{
		return $this->request;
	}

}
