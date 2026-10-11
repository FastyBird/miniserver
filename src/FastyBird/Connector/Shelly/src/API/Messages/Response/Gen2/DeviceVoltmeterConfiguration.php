<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen2;

use FastyBird\Connector\Shelly\API;
use FastyBird\Connector\Shelly\Types;
use Orisai\ObjectMapper;
use Override;

/**
 * Generation 2 device voltmeter configuration message
 */
final readonly class DeviceVoltmeterConfiguration implements API\Messages\Message
{

	public function __construct(
		#[ObjectMapper\Rules\IntValue(unsigned: true)]
		private int $id,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\StringValue(notEmpty: true),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private string|null $name,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(castNumericString: true),
			new ObjectMapper\Rules\NullValue(),
		])]
		#[ObjectMapper\Modifiers\FieldName('report_thr')]
		private float|null $reportThreshold,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\MappedObjectValue(class: VoltmeterXVoltageConfigurationBlock::class),
			new ObjectMapper\Rules\NullValue(),
		])]
		private VoltmeterXVoltageConfigurationBlock|null $xvoltage,
	)
	{
	}

	public function getId(): int
	{
		return $this->id;
	}

	public function getType(): Types\ComponentType
	{
		return Types\ComponentType::VOLTMETER;
	}

	public function getName(): string|null
	{
		return $this->name;
	}

	public function getReportThreshold(): float|null
	{
		return $this->reportThreshold;
	}

	public function getXvoltage(): VoltmeterXVoltageConfigurationBlock|null
	{
		return $this->xvoltage;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function toArray(): array
	{
		return [
			'id' => $this->getId(),
			'type' => $this->getType()->value,
			'name' => $this->getName(),
			'report_threshold' => $this->getReportThreshold(),
			'xvoltage' => $this->getXvoltage()?->toArray(),
		];
	}

}
