<?php declare(strict_types = 1);

namespace FastyBird\Core\Tests\Fixtures\Dummy;

use FastyBird\Core\Documents;
use Orisai\ObjectMapper;
use Override;

/**
 * A document RoutingDocumentFactory finds by its routing key
 */
#[Documents\Mapping\Document]
#[Documents\Mapping\RoutingMap([self::ROUTING_KEY])]
final class DummyRoutedDocument implements Documents\Document
{

	public const string ROUTING_KEY = 'fb.core.test.dummy.routed';

	/**
	 * @param array<string> $tags
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private readonly string $id,
		#[ObjectMapper\Rules\ListOf(item: new ObjectMapper\Rules\StringValue())]
		private readonly array $tags = [],
	)
	{
	}

	public function getId(): string
	{
		return $this->id;
	}

	/**
	 * @return array<string>
	 */
	public function getTags(): array
	{
		return $this->tags;
	}

	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'tags' => $this->getTags(),
		];
	}

}
