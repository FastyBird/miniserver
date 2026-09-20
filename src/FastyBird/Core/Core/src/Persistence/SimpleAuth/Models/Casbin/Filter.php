<?php declare(strict_types = 1);

/**
 * Filter.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     Models
 * @since          0.1.0
 *
 * @date           23.07.24
 */

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
