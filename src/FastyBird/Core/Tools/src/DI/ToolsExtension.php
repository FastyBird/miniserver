<?php declare(strict_types = 1);

/**
 * ToolsExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:Tools!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           21.10.22
 */

namespace FastyBird\Core\Tools\DI;

use FastyBird\Core\Tools\Helpers;
use FastyBird\Core\Tools\Schemas;
use FastyBird\Core\Tools\Utilities;
use Monolog;
use Nette;
use Nette\Bootstrap;
use Nette\DI;
use Nette\Schema;
use Sentry;
use stdClass;
use function assert;
use function class_exists;
use function getenv;
use function interface_exists;
use function is_string;

/**
 * API key plugin
 *
 * @package        FastyBird:Tools!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class ToolsExtension extends DI\CompilerExtension
{

	public const NAME = 'fbTools';

	public static function register(
		Bootstrap\Configurator $config,
		string $extensionName = self::NAME,
	): void
	{
		$config->onCompile[] = static function (
			Bootstrap\Configurator $config,
			DI\Compiler $compiler,
		) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'sentry' => Schema\Expect::structure(
				[
					'dsn' => Schema\Expect::string()->nullable(),
					'level' => Schema\Expect::int(Monolog\Level::Warning),
				],
			),
		]);
	}

	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * HELPERS
		 */

		if (class_exists('\Doctrine\DBAL\Connection') && class_exists('\Doctrine\ORM\EntityManager')) {
			$builder->addDefinition($this->prefix('helpers.database'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\Database::class);
		}

		/**
		 * UTILITIES
		 */

		$builder->addDefinition($this->prefix('utilities.doctrineDateProvider'), new DI\Definitions\ServiceDefinition())
			->setType(Utilities\DateTimeProvider::class);

		/**
		 * JSON SCHEMAS TOOLS
		 */

		$builder->addDefinition($this->prefix('schemas.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Schemas\Validator::class);

		/**
		 * SENTRY ISSUES LOGGER
		 */

		if (interface_exists('\Sentry\ClientInterface')) {
			$builder->addDefinition($this->prefix('helpers.sentry'), new DI\Definitions\ServiceDefinition())
				->setType(Helpers\Sentry::class);
		}

		if (
			isset($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& is_string($_ENV['FB_APP_PARAMETER__SENTRY_DSN'])
			&& $_ENV['FB_APP_PARAMETER__SENTRY_DSN'] !== ''
		) {
			$sentryDSN = $_ENV['FB_APP_PARAMETER__SENTRY_DSN'];

		} elseif (
			getenv('FB_APP_PARAMETER__SENTRY_DSN') !== false
			&& getenv('FB_APP_PARAMETER__SENTRY_DSN') !== ''
		) {
			$sentryDSN = getenv('FB_APP_PARAMETER__SENTRY_DSN');

		} elseif ($configuration->sentry->dsn !== null) {
			$sentryDSN = $configuration->sentry->dsn;

		} else {
			$sentryDSN = null;
		}

		if (is_string($sentryDSN) && $sentryDSN !== '') {
			$builder->addDefinition($this->prefix('sentry.handler'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->logging->sentry->level);

			$sentryClientBuilderService = $builder->addDefinition(
				$this->prefix('sentry.clientBuilder'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setFactory('Sentry\ClientBuilder::create')
				->setArguments([['dsn' => $sentryDSN]]);

			$builder->addDefinition($this->prefix('sentry.client'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\ClientInterface::class)
				// @phpstan-ignore-next-line argument.type (Nette ServiceDefinition::setFactory() accepts a [service, method] callable array at runtime)
				->setFactory([$sentryClientBuilderService, 'getClient']);

			$builder->addDefinition($this->prefix('sentry.hub'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\State\Hub::class);
		}
	}

	/**
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\DI\NotAllowedDuringResolvingException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * SENTRY
		 */

		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class);

		if ($sentryHandlerServiceName !== null) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));

			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			$sentryHandlerService = $builder->getDefinition($this->prefix('sentry.handler'));
			assert($sentryHandlerService instanceof DI\Definitions\ServiceDefinition);

			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}
	}

}
