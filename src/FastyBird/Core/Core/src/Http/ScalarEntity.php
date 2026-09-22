<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

final class ScalarEntity extends Entity
{

	public function __construct(mixed $value)
	{
		parent::__construct($value);
	}

	public static function from(mixed $value): self
	{
		return new self($value);
	}

}
