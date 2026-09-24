<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Exceptions;

use Doctrine\ORM;
use FastyBird\Core\Exceptions;
use RuntimeException;
use Throwable;

final class Query extends RuntimeException implements Exceptions\Exception
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
