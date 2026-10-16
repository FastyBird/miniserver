<?php declare(strict_types = 1);

namespace FastyBird\Module\Accounts\Entities;

/**
 * Entity params field interface
 */
interface EntityParams
{

	/**
	 * @param array<string, mixed> $params
	 */
	public function setParams(array $params): void;

	/**
	 * @return array<string, mixed>
	 */
	public function getParams(): array;

	public function setParam(string $key, mixed $value = null): void;

	public function getParam(string $key, mixed $default = null): mixed;

}
