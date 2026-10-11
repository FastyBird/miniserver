<?php declare(strict_types = 1);

namespace FastyBird\Plugin\RabbitMq\Utilities;

use Nette;
use Ramsey\Uuid;

/**
 * Pub/Sub messages identifier
 */
final class IdentifierGenerator
{

	use Nette\SmartObject;

	private string $identifier;

	public function __construct()
	{
		$this->identifier = Uuid\Uuid::uuid4()->toString();
	}

	public function getIdentifier(): string
	{
		return $this->identifier;
	}

}
