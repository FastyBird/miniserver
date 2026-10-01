<?php declare(strict_types = 1);

namespace FastyBird\Core\Clock\DI;

use DateInvalidTimeZoneException;
use DateTimeZone;
use FastyBird\Core\Clock;
use FastyBird\Core\Exceptions;
use Nette\DI;
use Nette\Schema;
use Override;
use stdClass;
use function assert;
use function in_array;

/**
 * The system clock, or a frozen one
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs as fbCore.clock and reads its fbCore > clock
 * section, so its services are fbCore.clock.system and fbCore.clock.frozen.
 */
final class ClockExtension extends DI\CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'timeZone' => Schema\Expect::string('UTC'),
			'system' => Schema\Expect::bool(true),
			'frozen' => Schema\Expect::anyOf(Schema\Expect::float(), Schema\Expect::mixed()),
		]);
	}

	/**
	 * @throws DateInvalidTimeZoneException
	 * @throws Exceptions\InvalidArgument
	 */
	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		if (!in_array($configuration->timeZone, DateTimeZone::listIdentifiers(), true)) {
			throw new Exceptions\InvalidArgument('Timezone have to be valid PHP timezone string');
		}

		if ($configuration->system) {
			$builder->addDefinition(
				$this->prefix('system'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Clock\SystemClock::class)
				->setArgument('timeZone', new DateTimeZone($configuration->timeZone))
				->setAutowired($configuration->frozen === null);
		}

		if ($configuration->frozen !== null) {
			$builder->addDefinition(
				$this->prefix('frozen'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Clock\FrozenClock::class)
				->setArguments([
					'timestamp' => $configuration->frozen,
					'timeZone' => new DateTimeZone($configuration->timeZone),
				]);
		}
	}

}
