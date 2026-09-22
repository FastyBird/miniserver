<?php declare(strict_types = 1);

namespace FastyBird\Core\Exceptions;

use FastyBird\Core\Entities\DoctrineCrud as Entities;
use Throwable;

class MissingRequiredField extends InvalidState
{

	public function __construct(
		private Entities\IEntity $entity,
		private string $field,
		string $message = '',
		int $code = 0,
		Throwable|null $previous = null,
	)
	{
		parent::__construct($message, $code, $previous);
	}

	public function getEntity(): Entities\IEntity
	{
		return $this->entity;
	}

	public function getField(): string
	{
		return $this->field;
	}

}
