<?php declare(strict_types = 1);

namespace FastyBird\Module\Triggers\Documents\Triggers;

use FastyBird\Core\Documents;
use FastyBird\Module\Triggers\Entities;
use Orisai\ObjectMapper;
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
		string $type,
		string $name,
		string|null $comment = null,
		bool $enabled = false,
		bool|null $isTriggered = null,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		#[ObjectMapper\Modifiers\FieldName('is_fulfilled')]
		private readonly bool|null $isFulfilled = null,
		Uuid\UuidInterface|null $owner = null,
	)
	{
		parent::__construct($id, $type, $name, $comment, $enabled, $isTriggered, $owner);
	}

	public function isFulfilled(): bool|null
	{
		return $this->isFulfilled;
	}

	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'is_fulfilled' => $this->isFulfilled(),
		]);
	}

}
