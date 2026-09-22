<?php declare(strict_types = 1);

namespace FastyBird\Core\Http;

abstract class Entity
{

	public function __construct(protected mixed $data = null)
	{
	}

	public function getData(): mixed
	{
		return $this->data;
	}

	protected function setData(mixed $data): void
	{
		$this->data = $data;
	}

}
