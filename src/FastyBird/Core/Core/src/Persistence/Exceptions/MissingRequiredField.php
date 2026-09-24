<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\Exceptions;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Persistence\Entities;
use Throwable;

final class MissingRequiredField extends Exceptions\InvalidState
{

	public function __construct(
		private Entities\CrudEntity $entity,
		private string $field,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

	public function getEntity(): Entities\CrudEntity
	{
		return $this->entity;
	}

	public function getField(): string
	{
		return $this->field;
	}

}
