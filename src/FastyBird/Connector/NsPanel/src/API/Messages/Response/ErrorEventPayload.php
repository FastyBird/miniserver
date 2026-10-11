<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages\Response;

use FastyBird\Connector\NsPanel\API;
use Orisai\ObjectMapper;
use stdClass;

/**
 * NS Panel event error payload response definition
 */
final readonly class ErrorEventPayload implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $type,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $description,
	)
	{
	}

	public function getType(): string
	{
		return $this->type;
	}

	public function getDescription(): string
	{
		return $this->description;
	}

	/**
	 * {@inheritDoc}
	 */
	public function toArray(): array
	{
		return [
			'type' => $this->getType(),
			'description' => $this->getDescription(),
		];
	}

	public function toJson(): object
	{
		$json = new stdClass();
		$json->type = $this->getType();
		$json->description = $this->getDescription();

		return $json;
	}

}
