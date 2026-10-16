<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use CuyZ\Valinor\Mapper\Configurator;
use DateTimeInterface;
use FastyBird\Core\Documents;
use Override;

/**
 * The Valinor twin of DummyParityOrisaiDocument
 */
final readonly class DummyParityValinorDocument implements Documents\Document
{

	/**
	 * @param list<string> $tags
	 */
	public function __construct(
		#[Documents\Mapping\RefuseBlank]
		private string $id,
		private array $tags = [],
		#[Documents\Mapping\MapBlankToNull]
		private string|null $name = null,
		#[Configurator\MapFromKey('updated_at')]
		private DateTimeInterface|null $updatedAt = null,
	)
	{
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->id,
			'tags' => $this->tags,
			'name' => $this->name,
			'updated_at' => $this->updatedAt?->format(DateTimeInterface::ATOM),
		];
	}

}
