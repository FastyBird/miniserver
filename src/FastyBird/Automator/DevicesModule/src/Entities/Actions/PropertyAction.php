<?php declare(strict_types = 1);

/**
 * PropertyAction.php
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

namespace FastyBird\Automator\DevicesModule\Entities\Actions;

use Doctrine\ORM\Mapping as ORM;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Mapping\DoctrineCrud\Attribute as IPubDoctrine;
use FastyBird\Core\Values\Types\Payloads;
use FastyBird\Core\Values\Utilities;
use FastyBird\Module\Triggers\Entities as TriggersEntities;
use Ramsey\Uuid;
use TypeError;
use ValueError;
use function array_merge;

#[ORM\MappedSuperclass]
abstract class PropertyAction extends TriggersEntities\Actions\Action
{

	#[IPubDoctrine\Crud(required: true)]
	#[ORM\Column(name: 'action_device', type: Uuid\Doctrine\UuidBinaryType::NAME, nullable: true)]
	protected Uuid\UuidInterface $device;

	#[IPubDoctrine\Crud(required: true, writable: true)]
	#[ORM\Column(name: 'action_value', type: 'string', nullable: true, length: 100)]
	protected string $value;

	public function __construct(
		Uuid\UuidInterface $device,
		string $value,
		TriggersEntities\Triggers\Trigger $trigger,
		Uuid\UuidInterface|null $id = null,
	)
	{
		parent::__construct($trigger, $id);

		$this->device = $device;
		$this->value = $value;
	}

	/**
	 * @throws TypeError
	 * @throws ValueError
	 */
	public function getValue(): string|Payloads\Payload
	{
		if (Payloads\Button::tryFrom($this->value) !== null) {
			return Payloads\Button::from($this->value);
		}

		if (Payloads\Switcher::tryFrom($this->value) !== null) {
			return Payloads\Switcher::from($this->value);
		}

		if (Payloads\Cover::tryFrom($this->value) !== null) {
			return Payloads\Cover::from($this->value);
		}

		return $this->value;
	}

	public function validate(string $value): bool
	{
		return $this->value === $value;
	}

	public function getDevice(): Uuid\UuidInterface
	{
		return $this->device;
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
			'value' => Utilities\Value::toString($this->getValue()),
		]);
	}

}
