<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API\Messages;

use FastyBird\Connector\NsPanel\Types;
use Orisai\ObjectMapper;
use Override;
use stdClass;

/**
 * Request & Response header
 */
final readonly class Header implements Message
{

	public function __construct(
		#[ObjectMapper\Rules\BackedEnumValue(class: Types\Header::class)]
		private Types\Header $name,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		#[ObjectMapper\Modifiers\FieldName('message_id')]
		private string $messageId,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $version,
	)
	{
	}

	public function getName(): Types\Header
	{
		return $this->name;
	}

	public function getMessageId(): string
	{
		return $this->messageId;
	}

	public function getVersion(): string
	{
		return $this->version;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'name' => $this->getName()->value,
			'message_id' => $this->getMessageId(),
			'version' => $this->getVersion(),
		];
	}

	#[Override]
	public function toJson(): object
	{
		$json = new stdClass();
		$json->name = $this->getName()->value;
		$json->message_id = $this->getMessageId();
		$json->version = $this->getVersion();

		return $json;
	}

}
