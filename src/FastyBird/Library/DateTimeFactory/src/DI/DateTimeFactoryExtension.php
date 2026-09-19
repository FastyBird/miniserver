<?php declare(strict_types = 1);

/**
 * DateTimeFactoryExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:DateTimeFactory!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           08.03.20
 */

namespace FastyBird\Library\DateTimeFactory\DI;

use DateTimeZone;
use FastyBird\Library\DateTimeFactory;
use FastyBird\Library\DateTimeFactory\Exceptions;
use Nette;
use Nette\DI;
use Nette\Schema;
use stdClass;
use function assert;
use function in_array;

/**
 * Date&Time factory extension container
 *
 * @package        FastyBird:DateTimeFactory!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class DateTimeFactoryExtension extends DI\CompilerExtension
{

	public static function register(
		Nette\Bootstrap\Configurator $config,
		string $extensionName = 'dateTimeFactory',
	): void
	{
		$config->onCompile[] = static function (
			Nette\Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'timeZone' => Schema\Expect::string('UTC'),
			'system' => Schema\Expect::bool(true),
			'frozen' => Schema\Expect::anyOf(Schema\Expect::float(), Schema\Expect::mixed()),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		if (
			!in_array($configuration->timeZone, DateTimeZone::listIdentifiers(), true)
		) {
			throw new Exceptions\InvalidArgument('Timezone have to be valid PHP timezone string');
		}

		if ($configuration->system) {
			$builder->addDefinition($this->prefix('datetime.system'), new DI\Definitions\ServiceDefinition())
				->setType(DateTimeFactory\SystemClock::class)
				->setArgument('timeZone', new DateTimeZone($configuration->timeZone))
				->setAutowired($configuration->frozen === null);
		}

		if ($configuration->frozen !== null) {
			$builder->addDefinition($this->prefix('datetime.frozen'), new DI\Definitions\ServiceDefinition())
				->setType(DateTimeFactory\FrozenClock::class)
				->setArguments([
					'timestamp' => $configuration->frozen,
					'timeZone' => new DateTimeZone($configuration->timeZone),
				]);
		}
	}

}
