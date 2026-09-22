<?php declare(strict_types = 1);

namespace FastyBird\Core\Persistence\SimpleAuth\Models\Casbin;

class Filter
{

	/**
	 * @param array<int, mixed>|array<string, mixed> $params
	 */
	public function __construct(private readonly string $predicates, private readonly array $params)
	{
	}

	public function getPredicates(): string
	{
		return $this->predicates;
	}

	/**
	 * @return array<int, mixed>|array<string, mixed>
	 */
	public function getParams(): array
	{
		return $this->params;
	}

}
