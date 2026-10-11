<?php declare(strict_types = 1);

namespace FastyBird\Module\Ui\Entities;

use Nette\Utils;

/**
 * Entity params field interface
 */
interface EntityParams
{

	/**
	 * @param array<string, mixed> $params
	 */
	public function setParams(array $params): void;

	public function getParams(): Utils\ArrayHash;

	public function setParam(string $key, mixed $value = null): void;

	public function getParam(string $key, mixed $default = null): mixed;

}
