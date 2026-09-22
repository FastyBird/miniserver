<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions;

use Doctrine\ORM;
use RuntimeException;
use Throwable;

class Query extends RuntimeException implements Exception
{

	public function __construct(
		Throwable $previous,
		public ORM\AbstractQuery|null $query = null,
		string|null $message = null,
	)
	{
		parent::__construct($message ?? $previous->getMessage(), 0, $previous);
	}

}
