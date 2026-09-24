<?php declare(strict_types = 1);

/**
 * PropertyCondition.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DevicesModuleAutomator!
 * @subpackage     Entities
 * @since          1.0.0
 *
 * @date           04.04.20
 */

namespace FastyBird\Automator\DevicesModule\Entities\Conditions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use FastyBird\Module\Triggers\Types as TriggersTypes;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;

#[ORM\MappedSuperclass]
abstract class PropertyCondition extends TriggersEntities\Conditions\Condition
{

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'condition_device', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	protected Uuid\UuidInterface $device;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(
		name: 'condition_operator',
		type: 'string',
		length: 15,
		nullable: true,
		enumType: TriggersTypes\ConditionOperator::class,
	)]
	protected TriggersTypes\ConditionOperator $operator;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'condition_operand', type: 'string', nullable: true, length: 20)]
	protected string $operand;

	public function __construct(
		Uuid\UuidInterface $device,
		TriggersTypes\ConditionOperator $operator,
		string $operand,
		TriggersEntities\Triggers\Automatic $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->device = $device;
		$this->operator = $operator;
		$this->operand = $operand;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
	}

	public function getOperator(): TriggersTypes\ConditionOperator
	{
		return $this->operator;
	}

	public function setOperator(TriggersTypes\ConditionOperator $operator): void
	{
		$this->operator = $operator;
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getOperand(): string|Payloads\Payload
	{
		if (Payloads\Button::tryFrom($this->operand) !== null) {
			return Payloads\Button::from($this->operand);
		}

		if (Payloads\Switcher::tryFrom($this->operand) !== null) {
			return Payloads\Switcher::from($this->operand);
		}

		if (Payloads\Cover::tryFrom($this->operand) !== null) {
			return Payloads\Cover::from($this->operand);
		}

		return $this->operand;
	}

	public function setOperand(string $operand): void
	{
		$this->operand = $operand;
	}

	public function validate(string $value): bool
	{
		if ($this->operator === TriggersTypes\ConditionOperator::EQUAL) {
			return $this->operand === $value;
		}

		if ($this->operator === TriggersTypes\ConditionOperator::ABOVE) {
			return (float) ($this->operand) < (float) $value;
		}

		return (float) ($this->operand) > (float) $value;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exceptions\InvalidArgument
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function toArray(): array
	{
		return array_merge(parent::toArray(), [
			'device' => $this->getDevice()->toString(),
			'operator' => $this->getOperator()->value,
			'operand' => Utilities\Value::toString($this->getOperand()),
		]);
	}

}
