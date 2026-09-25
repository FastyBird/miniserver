<?php declare(strict_types = 1);

namespace FastyBird\Core\Http\Exceptions;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

abstract class HttpSpecialized extends Http
{

	public function __construct(
		ServerRequestInterface $request,
		string|null $message = null,
		Throwable|null $previous = null,
	)
	{
		if ($message !== null) {
			$this->message = $message;
		}

		parent::__construct($request, $this->message, $this->code, $previous);
	}

}
