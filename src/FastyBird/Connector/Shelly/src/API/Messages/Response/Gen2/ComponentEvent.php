<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\API;
use Nette\Utils;
use Orisai\ObjectMapper;
use function intval;

/**
 * Generation 2 device component event message
 */
final readonly class ComponentEvent implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $component,
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $id,
		#[ObjectMapper\Rules\StringValue(notEmpty: true)]
		private string $event,
		#[ObjectMapper\Rules\FloatValue(unsigned: true)]
		private float $timestamp,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\IntValue(),
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\BoolValue(),
			new ObjectMapper\Rules\StringValue(),
			new ObjectMapper\Rules\NullValue(),
		])]
		private int|float|bool|string|null $data = null,
	)
	{
	}

	public function getComponent(): string
	{
		return $this->component;
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getEvent(): string
	{
		return $this->event;
	}

	/**
	 * @throws Exception
	 */
	public function getTimestamp(): DateTimeInterface
	{
		return Utils\DateTime::from(intval($this->timestamp));
	}

	public function getData(): float|bool|int|string|null
	{
		return $this->data;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'component' => $this->getComponent(),
			'id' => $this->getId(),
			'event' => $this->getEvent(),
			'timestamp' => $this->getTimestamp()->format(DateTimeInterface::ATOM),
			'data' => $this->getData(),
		];
	}

}
