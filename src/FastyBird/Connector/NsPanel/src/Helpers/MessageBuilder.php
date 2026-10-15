<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\Helpers;

use FastyBird\Connector\NsPanel\API;
use FastyBird\Connector\NsPanel\Exceptions;
use FastyBird\Connector\NsPanel\Queue;
use FastyBird\Core\Documents;

/**
 * Message builder
 */
final readonly class MessageBuilder
{

	public function __construct(private Documents\Mapping\Mapper $mapper)
	{
	}

	/**
	 * @template T of API\Messages\Message|Queue\Messages\Message
	 *
	 * @param class-string<T> $message
	 * @param array<mixed> $data
	 *
	 * @return T
	 *
	 * @throws Exceptions\Runtime
	 */
	public function create(
		string $message,
		array $data,
	): API\Messages\Message|Queue\Messages\Message
	{
		try {
			return $this->mapper->map(
				$data,
				$message,
				new Documents\Mapping\MappingOptions(allowUnknownFields: true),
			);
		} catch (Documents\Exceptions\MappingFailed $ex) {
			throw new Exceptions\Runtime('Could not map data to message: ' . $ex->getMessage());
		}
	}

}
