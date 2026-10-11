<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API\Messages\Response\Cloud;

use FastyBird\Connector\Sonoff\API;
use Orisai\ObjectMapper;
use Override;
use function array_map;

/**
 * User homes list entity
 */
final readonly class Family implements API\Messages\Message
{

	/**
	 * @param array<Home> $homes
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('currentFamilyId')]
		private string $familyId,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Home::class),
		)]
		#[ObjectMapper\Modifiers\FieldName('familyList')]
		private array $homes,
	)
	{
	}

	public function getFamilyId(): string
	{
		return $this->familyId;
	}

	/**
	 * @return array<Home>
	 */
	public function getHomes(): array
	{
		return $this->homes;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'family_id' => $this->getFamilyId(),
			'homes' => array_map(static fn (Home $home): array => $home->toArray(), $this->getHomes()),
		];
	}

}
