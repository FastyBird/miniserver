<?php declare(strict_types = 1);

namespace FastyBird\Connector\Viera\API\Messages\Response;

use FastyBird\Connector\Viera\API;
use Orisai\ObjectMapper;
use function array_map;

/**
 * Device apps info message
 */
readonly class DeviceApps implements API\Messages\Message
{

	/**
	 * @param array<Application> $apps
	 */
	public function __construct(
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\MappedObjectValue(Application::class),
		)]
		private array $apps = [],
	)
	{
	}

	/**
	 * @return array<Application>
	 */
	public function getApps(): array
	{
		return $this->apps;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'applications' => array_map(static fn (Application $app): array => $app->toArray(), $this->getApps()),
		];
	}

}
