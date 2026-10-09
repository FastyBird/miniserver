<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

abstract class Entity
{

	public function __construct(public protected(set) mixed $data = null)
	{
	}

}
