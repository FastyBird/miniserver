<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Helpers;

use FastyBird\Connector\FbMqtt\Exceptions;
use FastyBird\Connector\FbMqtt\Queue;
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
	 * @template T of Queue\Messages\Message
	 *
	 * @param class-string<T> $message
	 * @param array<mixed> $data
	 *
	 * @throws Exceptions\Runtime
	 */
	public function create(
		string $message,
		array $data,
	): Queue\Messages\Message
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
