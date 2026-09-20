<?php declare(strict_types = 1);

/**
 * ScalarEntity.php
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

namespace FastyBird\Core\Http\WebServer;

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
