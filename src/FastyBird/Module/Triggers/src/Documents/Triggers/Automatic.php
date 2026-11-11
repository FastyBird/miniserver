<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers;

use CuyZ\Valinor\Mapper\Configurator;
use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Entities;
use Override;
use Ramsey\Uuid;
use function array_merge;

/**
 * Automatic  trigger document
 */
#[Documents\Mapping\Document(entity: Entities\Triggers\Automatic::class)]
#[Documents\Mapping\DiscriminatorEntry(name: Entities\Triggers\Automatic::TYPE)]
final class Automatic extends Trigger
{

	public function __construct(
		Uuid\UuidInterface $id,
		#[Documents\Mapping\RefuseBlank]
		string $type,
		#[Documents\Mapping\RefuseBlank]
		string $name,
		#[Documents\Mapping\MapBlankToNull]
		string|null $comment = null,
		bool $enabled = false,
		#[Documents\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_triggered')]
		bool|null $isTriggered = null,
		#[Documents\Mapping\MapBlankToNull]
		#[Configurator\MapFromKey('is_fulfilled')]
		private readonly bool|null $isFulfilled = null,
		#[Documents\Mapping\MapBlankToNull]
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $type, $name, $comment, $enabled, $isTriggered, $owner);
	}

	public function isFulfilled(): bool|null
	{
		return $this->isFulfilled;
	}

	#[Override]
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'is_fulfilled' => $this->isFulfilled(),
		]);
	}

}
