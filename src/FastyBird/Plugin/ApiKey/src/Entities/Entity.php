<?php declare(strict_types = 1);

namespace FastyBird\Plugin\ApiKey\Entities;

use Ramsey\Uuid;

/**
 * Base entity interface
 */
interface Entity
{

	public function getId(): Uuid\UuidInterface;

}
