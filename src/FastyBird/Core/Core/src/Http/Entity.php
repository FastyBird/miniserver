<?php declare(strict_types = 1);

/**
 * Entity.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Http
 * @since          1.0.0
 *
 * @date           17.03.20
 */

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
