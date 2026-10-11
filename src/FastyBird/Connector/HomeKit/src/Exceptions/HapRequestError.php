<?php declare(strict_types = 1);

namespace FastyBird\Connector\HomeKit\Exceptions;

use FastyBird\Connector\HomeKit\Types;
use FastyBird\Core\Http\Exceptions;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

final class HapRequestError extends Exceptions\Http implements Exception
{

	public function __construct(
		ServerRequestInterface $request,
		private readonly Types\ServerStatus $error,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($request, $message, $code, $previous);
	}

	public function getError(): Types\ServerStatus
	{
		return $this->error;
	}

}
