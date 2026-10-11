<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API\Messages\Response\Gen1;

use DateTimeInterface;
use Exception;
use FastyBird\Connector\Shelly\API;
use Nette\Utils;
use Orisai\ObjectMapper;
use function intval;

/**
 * Generation 1 device meter state message
 */
final readonly class DeviceMeterState implements API\Messages\Message
{

	/**
	 * @param array<float> $counters
	 */
	public function __construct(
		#[ObjectMapper\Rules\FloatValue()]
		private float $power,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\BoolValue(),
		])]
		private float|bool $overpower,
		#[ObjectMapper\Rules\BoolValue(castBoolLike: true)]
		#[ObjectMapper\Modifiers\FieldName('is_valid')]
		private bool $valid,
		#[ObjectMapper\Rules\AnyOf([
			new ObjectMapper\Rules\FloatValue(),
			new ObjectMapper\Rules\NullValue(castEmptyString: true),
		])]
		private float|null $timestamp,
		#[ObjectMapper\Rules\ArrayOf(
			new ObjectMapper\Rules\FloatValue(),
		)]
		private array $counters,
		#[ObjectMapper\Rules\FloatValue()]
		private float $total,
	)
	{
	}

	public function getPower(): float
	{
		return $this->power;
	}

	public function getOverpower(): float|bool
	{
		return $this->overpower;
	}

	public function isValid(): bool
	{
		return $this->valid;
	}

	/**
	 * @throws Exception
	 */
	public function getTimestamp(): DateTimeInterface|null
	{
		if ($this->timestamp !== null) {
			return Utils\DateTime::from(intval($this->timestamp));
		}

		return null;
	}

	/**
	 * @return array<float>
	 */
	public function getCounters(): array
	{
		return $this->counters;
	}

	public function getTotal(): float
	{
		return $this->total;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @throws Exception
	 */
	public function toArray(): array
	{
		return [
			'power' => $this->getPower(),
			'overpower' => $this->getOverpower(),
			'valid' => $this->isValid(),
			'timestamp' => $this->getTimestamp()?->format(DateTimeInterface::ATOM),
			'counters' => $this->getCounters(),
			'total' => $this->getTotal(),
		];
	}

}
