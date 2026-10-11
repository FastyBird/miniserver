<?php declare(strict_types = 1);

namespace FastyBird\Connector\Tuya\API\Messages\Response;

use FastyBird\Connector\Tuya\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * OpenAPI user device specifications message
 */
final readonly class UserDeviceSpecifications implements API\Messages\Message
{

	/**
	 * @param array<UserDeviceSpecificationsFunction> $functions
	 * @param array<UserDeviceSpecificationsState> $status
	 */
	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $category,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceSpecificationsFunction::class),
		)]
		private array $functions,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(UserDeviceSpecificationsState::class),
		)]
		private array $status,
	)
	{
	}

	public function getCategory(): string
	{
		return $this->category;
	}

	/**
	 * @return array<UserDeviceSpecificationsFunction>
	 */
	public function getFunctions(): array
	{
		return $this->functions;
	}

	/**
	 * @return array<UserDeviceSpecificationsState>
	 */
	public function getStatus(): array
	{
		return $this->status;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'category' => $this->getCategory(),
			'functions' => array_map(
				static fn (UserDeviceSpecificationsFunction $item): array => $item->toArray(),
				$this->getFunctions(),
			),
			'status' => array_map(
				static fn (UserDeviceSpecificationsState $item): array => $item->toArray(),
				$this->getStatus(),
			),
		];
	}

}
