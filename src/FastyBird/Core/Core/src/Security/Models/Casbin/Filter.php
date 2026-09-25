<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\Models\Casbin;

final readonly class Filter
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
