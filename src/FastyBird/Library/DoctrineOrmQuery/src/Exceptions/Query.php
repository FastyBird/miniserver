<?php declare(strict_types = 1);

/**
 * QueryException.php
 *
 * @copyright      More in LICENSE.md
 * @license        https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        iPublikuj:DoctrineOrmQuery!
 * @subpackage     Exceptions
 * @since          0.0.1
 *
 * @date           10.11.19
 */

namespace FastyBird\Library\DoctrineOrmQuery\Exceptions;

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
