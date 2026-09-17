<?php declare(strict_types = 1);

/**
 * SimpleAuthExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:SimpleAuth!
 * @subpackage     DI
 * @since          0.1.0
 *
 * @date           09.07.20
 */

namespace FastyBird\Core\SimpleAuth\DI;

use Casbin;
use FastyBird\Core\SimpleAuth;
use FastyBird\Core\SimpleAuth\Access;
use FastyBird\Core\SimpleAuth\Events;
use FastyBird\Core\SimpleAuth\Exceptions;
use FastyBird\Core\SimpleAuth\Mapping;
use FastyBird\Core\SimpleAuth\Middleware;
use FastyBird\Core\SimpleAuth\Security;
use FastyBird\Core\SimpleAuth\Subscribers;
use Nette;
use Nette\Application as NetteApplication;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Nettrine\ORM as NettrineORM;
use stdClass;
use Symfony\Contracts\EventDispatcher;
use function assert;
use function is_file;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * Authentication helpers extension container
 *
 * @package        FastyBird:SimpleAuth!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class SimpleAuthExtension extends DI\CompilerExtension
{

	public static function register(
		Nette\Bootstrap\Configurator $config,
		string $extensionName = 'fbSimpleAuth',
	): void
	{
		$config->onCompile[] = static function (Nette\Bootstrap\Configurator $config, DI\Compiler $compiler) use ($extensionName): void {
			$compiler->addExtension($extensionName, new self());
		};
	}

	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			'token' => Schema\Expect::structure([
				'issuer' => Schema\Expect::string(),
				// Deliberately no default. This is the JWT signing key: a default means any
				// deployment that forgets to configure it signs tokens with a value published
				// on Packagist, and does so silently. Absent configuration has to fail.
				'signature' => Schema\Expect::string()->required(),
			]),
			'enable' => Schema\Expect::structure([
				'middleware' => Schema\Expect::bool(false),
				'doctrine' => Schema\Expect::structure([
					'mapping' => Schema\Expect::bool(false),
					'models' => Schema\Expect::bool(false),
				]),
				'casbin' => Schema\Expect::structure([
					'database' => Schema\Expect::bool(false),
				]),
				'nette' => Schema\Expect::structure([
					'application' => Schema\Expect::bool(false),
				]),
			]),
			'application' => Schema\Expect::structure([
				'signInUrl' => Schema\Expect::string(),
				'homeUrl' => Schema\Expect::string('/'),
			]),
			'services' => Schema\Expect::structure([
				'identity' => Schema\Expect::bool(false),
			]),
			'casbin' => Schema\Expect::structure([
				'model' => Schema\Expect::string(
					// phpcs:ignore SlevomatCodingStandard.Files.LineLength.LineTooLong
					__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'model.conf',
				),
				'policy' => Schema\Expect::string(),
			]),
		]);
	}

	/**
	 * @throws Exceptions\Logical
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('auth'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuth\Auth::class);

		$builder->addDefinition($this->prefix('configuration'), new DI\Definitions\ServiceDefinition())
			->setType(SimpleAuth\Configuration::class)
			->setArguments([
				'tokenIssuer' => $configuration->token->issuer,
				'tokenSignature' => $configuration->token->signature,
				'enableMiddleware' => $configuration->enable->middleware,
				'enableDoctrineMapping' => $configuration->enable->doctrine->mapping,
				'enableDoctrineModels' => $configuration->enable->doctrine->models,
				'enableNetteApplication' => $configuration->enable->nette->application,
				'applicationSignInUrl' => $configuration->application->signInUrl,
				'applicationHomeUrl' => $configuration->application->homeUrl,
			]);

		/**
		 * Token utilities
		 */

		$builder->addDefinition($this->prefix('token.builder'), new DI\Definitions\ServiceDefinition())
			->setType(Security\TokenBuilder::class)
			->setArgument('tokenSignature', $configuration->token->signature)
			->setArgument('tokenIssuer', $configuration->token->issuer);

		$builder->addDefinition($this->prefix('token.reader'), new DI\Definitions\ServiceDefinition())
			->setType(Security\TokenReader::class);

		$builder->addDefinition($this->prefix('token.validator'), new DI\Definitions\ServiceDefinition())
			->setType(Security\TokenValidator::class)
			->setArgument('tokenSignature', $configuration->token->signature)
			->setArgument('tokenIssuer', $configuration->token->issuer);

		/**
		 * User security
		 */

		if ($configuration->services->identity) {
			$builder->addDefinition($this->prefix('security.identityFactory'), new DI\Definitions\ServiceDefinition())
				->setType(Security\IdentityFactory::class);
		}

		$builder->addDefinition($this->prefix('security.userStorage'), new DI\Definitions\ServiceDefinition())
			->setType(Security\UserStorage::class);

		/**
		 * Access checks
		 */

		$builder->addDefinition($this->prefix('access.annotationChecker'), new DI\Definitions\ServiceDefinition())
			->setType(Access\AnnotationChecker::class);

		$builder->addDefinition($this->prefix('access.latteChecker'), new DI\Definitions\ServiceDefinition())
			->setType(Access\LatteChecker::class);

		$builder->addDefinition($this->prefix('access.linkChecker'), new DI\Definitions\ServiceDefinition())
			->setType(Access\LinkChecker::class);

		/**
		 * Casbin
		 */

		if ($configuration->enable->casbin->database) {
			$adapter = $builder->addDefinition(
				$this->prefix('casbin.adapter'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SimpleAuth\Models\Casbin\Adapter::class);

			$builder->addDefinition($this->prefix('casbin.subscriber'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\Policy::class);
		} else {
			$policyFile = $configuration->casbin->policy;

			if (!is_string($policyFile) || !is_file($policyFile)) {
				throw new Exceptions\Logical('Casbin policy file is not configured');
			}

			$adapter = $builder->addDefinition($this->prefix('casbin.adapter'), new DI\Definitions\ServiceDefinition())
				->setType(Casbin\Persist\Adapters\FileAdapter::class)
				->setArguments([
					'filePath' => $policyFile,
				]);
		}

		$modelFile = $configuration->casbin->model;

		if (!is_string($modelFile) || !is_file($modelFile)) {
			throw new Exceptions\Logical('Casbin model file is not configured');
		}

		$builder->addDefinition($this->prefix('casbin.enforcerFactory'), new DI\Definitions\ServiceDefinition())
			->setType(Security\EnforcerFactory::class)
			->setArguments([
				'modelFile' => $modelFile,
				'adapter' => $adapter,
			]);

		/**
		 * Web server extension
		 */

		if ($configuration->enable->middleware) {
			$builder->addDefinition($this->prefix('middleware.access'), new DI\Definitions\ServiceDefinition())
				->setType(Middleware\Authorization::class);

			$builder->addDefinition($this->prefix('middleware.user'), new DI\Definitions\ServiceDefinition())
				->setType(Middleware\User::class);
		}

		/**
		 * Doctrine extension
		 */

		if ($configuration->enable->doctrine->mapping) {
			$builder->addDefinition($this->prefix('doctrine.driver'), new DI\Definitions\ServiceDefinition())
				->setType(Mapping\Driver\Owner::class);

			$builder->addDefinition($this->prefix('doctrine.subscriber'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\User::class);
		}

		if ($configuration->enable->doctrine->models) {
			$builder->addDefinition($this->prefix('doctrine.tokensRepository'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuth\Models\Tokens\Repository::class);

			$builder->addDefinition($this->prefix('doctrine.tokensManager'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuth\Models\Tokens\Manager::class);
		}

		if ($configuration->enable->casbin->database) {
			$builder->addDefinition(
				$this->prefix('doctrine.policiesRepository'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(SimpleAuth\Models\Policies\Repository::class);

			$builder->addDefinition($this->prefix('doctrine.policiesManager'), new DI\Definitions\ServiceDefinition())
				->setType(SimpleAuth\Models\Policies\Manager::class);
		}

		/**
		 * Nette application extension
		 */

		if ($configuration->enable->nette->application) {
			$builder->addDefinition($this->prefix('nette.application'), new DI\Definitions\ServiceDefinition())
				->setType(Subscribers\Application::class);
		}
	}

	/**
	 * @throws DI\MissingServiceException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		/**
		 * Security
		 */

		$userContextServiceName = $builder->getByType(Security\User::class);

		$userContext = null;

		if ($userContextServiceName !== null) {
			$userContext = $builder->getDefinition($userContextServiceName);
		}

		if ($userContext === null) {
			$builder->addDefinition($this->prefix('security.user'), new DI\Definitions\ServiceDefinition())
				->setType(Security\User::class);
		}

		/**
		 * Doctrine extension
		 */

		if (
			$configuration->enable->doctrine->models
			|| $configuration->enable->casbin->database
		) {
			// nettrine/orm 0.10 tags the per-manager MappingDriverChain rather than an
			// AttributeDriver, and the old nettrineOrmAttributes.attributeDriver service is gone.
			NettrineORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
				'default',
				'FastyBird\Core\SimpleAuth\Entities',
				__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'Entities',
			);
		}

		/**
		 * Nette application extension
		 */

		if ($configuration->enable->nette->application) {
			if ($builder->getByType(EventDispatcher\EventDispatcherInterface::class) !== null) {
				if ($builder->getByType(NetteApplication\Application::class) !== null) {
					$dispatcher = $builder->getDefinition(
						$builder->getByType(EventDispatcher\EventDispatcherInterface::class),
					);

					$application = $builder->getDefinition($builder->getByType(NetteApplication\Application::class));
					assert($application instanceof DI\Definitions\ServiceDefinition);

					$application->addSetup('?->onRequest[] = function() {?->dispatch(new ?(...func_get_args()));}', [
						'@self',
						$dispatcher,
						new PhpGenerator\Literal(Events\Request::class),
					]);

					$application->addSetup('?->onResponse[] = function() {?->dispatch(new ?(...func_get_args()));}', [
						'@self',
						$dispatcher,
						new PhpGenerator\Literal(Events\Response::class),
					]);
				}
			}
		}
	}

}
