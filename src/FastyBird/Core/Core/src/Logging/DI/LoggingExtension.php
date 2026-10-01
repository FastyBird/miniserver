<?php declare(strict_types = 1);

namespace FastyBird\Core\Logging\DI;

use FastyBird\Core\Logging;
use FastyBird\Core\Logging\Subscribers;
use Monolog;
use Nette\DI;
use Override;
use Sentry;
use stdClass;
use Symfony\Bridge\Monolog as BridgeMonolog;
use function assert;
use function getenv;
use function interface_exists;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * Logging: the Monolog handlers, the console log subscriber and Sentry
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs under the composite's name, so its services are
 * fbCore.application.logger.*, fbCore.application.subscribers.console, fbCore.tools.helpers.sentry
 * and fbCore.tools.sentry.*. It reads two sections, fbCore > application > logging and
 * fbCore > tools > sentry, so until the keys are renamed (#557) it is given the composite's
 * whole configuration, whose schema declares both.
 */
final class LoggingExtension extends DI\CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		if ($configuration->application->logging->rotatingFile->enabled === true) {
			$builder->addDefinition(
				$this->prefix('application.logger.handler.rotatingFile'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\RotatingFileHandler::class)
				->setArguments([
					'filename' => FB_LOGS_DIR . DIRECTORY_SEPARATOR . $configuration->application->logging->rotatingFile->filename,
					'maxFiles' => 10,
					'level' => $configuration->application->logging->rotatingFile->level,
				]);
		}

		if ($configuration->application->logging->stdOut->enabled === true) {
			$builder->addDefinition(
				$this->prefix('application.logger.handler.stdOut'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Monolog\Handler\StreamHandler::class)
				->setArguments([
					'stream' => 'php://stdout',
					'level' => $configuration->application->logging->stdOut->level,
				]);
		}

		$consoleHandler = null;

		if ($configuration->application->logging->console->enabled) {
			$consoleHandler = $builder->addDefinition(
				$this->prefix('application.logger.handler.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(BridgeMonolog\Handler\ConsoleHandler::class);
		}

		if ($configuration->application->logging->console->enabled) {
			$builder->addDefinition(
				$this->prefix('application.subscribers.console'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Subscribers\Console::class)
				->setArguments([
					'handler' => $consoleHandler,
					'level' => $configuration->application->logging->console->level,
				]);
		}

		if (interface_exists('\Sentry\ClientInterface')) {
			$builder->addDefinition($this->prefix('tools.helpers.sentry'), new DI\Definitions\ServiceDefinition())
				->setType(Logging\Sentry::class);
		}

		// Preserved from ToolsExtension::loadConfiguration() -- the DSN can come from the OS
		// environment directly (both $_ENV and getenv(), containers set it either way), and only
		// falls back to the NEON-configured value if neither is present. Dropping this fallback
		// would silently disable Sentry for every deployment that wires the DSN via environment
		// only, which is how the original packages -- and this repo's own docker/ setup -- do it.
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
		} elseif ($configuration->tools->sentry->dsn !== null) {
			$sentryDSN = $configuration->tools->sentry->dsn;
		} else {
			$sentryDSN = null;
		}

		if (is_string($sentryDSN) && $sentryDSN !== '') {
			$builder->addDefinition($this->prefix('tools.sentry.handler'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\Monolog\Handler::class)
				->setArgument('level', $configuration->tools->sentry->level);

			$sentryClientBuilderService = $builder->addDefinition(
				$this->prefix('tools.sentry.clientBuilder'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setFactory('Sentry\ClientBuilder::create')
				->setArguments([['dsn' => $sentryDSN]]);

			$builder->addDefinition($this->prefix('tools.sentry.client'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\ClientInterface::class)
				// @phpstan-ignore argument.type (Nette ServiceDefinition::setFactory() accepts a [service, method] callable array at runtime)
				->setFactory([$sentryClientBuilderService, 'getClient']);

			$builder->addDefinition($this->prefix('tools.sentry.hub'), new DI\Definitions\ServiceDefinition())
				->setType(Sentry\State\Hub::class);
		}
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * Rotating file and stdout handlers
		 */

		if (
			$configuration->application->logging->rotatingFile->enabled === true
			|| $configuration->application->logging->stdOut->enabled === true
		) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);

			if ($configuration->application->logging->rotatingFile->enabled === true) {
				$monologLoggerService->addSetup('?->pushHandler(?)', [
					'@self',
					$builder->getDefinition($this->prefix('application.logger.handler.rotatingFile')),
				]);
			}

			if ($configuration->application->logging->stdOut->enabled === true) {
				$monologLoggerService->addSetup('?->pushHandler(?)', [
					'@self',
					$builder->getDefinition($this->prefix('application.logger.handler.stdOut')),
				]);
			}
		}

		/**
		 * Sentry handler, pushed after the two above
		 */

		$sentryHandlerServiceName = $builder->getByType(Sentry\Monolog\Handler::class);

		if ($sentryHandlerServiceName !== null) {
			$monologLoggerServiceName = $builder->getByType(Monolog\Logger::class);
			assert(is_string($monologLoggerServiceName));
			$monologLoggerService = $builder->getDefinition($monologLoggerServiceName);
			assert($monologLoggerService instanceof DI\Definitions\ServiceDefinition);
			$sentryHandlerService = $builder->getDefinition($this->prefix('tools.sentry.handler'));
			assert($sentryHandlerService instanceof DI\Definitions\ServiceDefinition);
			$monologLoggerService->addSetup('?->pushHandler(?)', ['@self', $sentryHandlerService]);
		}
	}

}
