<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\Helpers;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Exceptions;
use FastyBird\Connector\Shelly\Queue;
use Orisai\ObjectMapper;

/**
 * Message builder
 */
final readonly class MessageBuilder
{

	public function __construct(
		private ObjectMapper\Processing\Processor $processor,
	)
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
			$options = new ObjectMapper\Processing\Options();
			$options->setAllowUnknownFields();

			return $this->processor->process($data, $message, $options);
		} catch (ObjectMapper\Exception\InvalidData $ex) {
			$errorPrinter = new ObjectMapper\Printers\ErrorVisualPrinter(
				new ObjectMapper\Printers\TypeToStringConverter(),
			);

			throw new Exceptions\Runtime('Could not map data to message: ' . $errorPrinter->printError($ex));
		}
	}

}
