<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions;

use RuntimeException;
use Throwable;

class EntityCreation extends RuntimeException implements Exception
{

	public function __construct(
		private string $field,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

	public function getField(): string
	{
		return $this->field;
	}

}
