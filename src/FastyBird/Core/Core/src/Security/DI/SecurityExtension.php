<?php declare(strict_types = 1);

namespace FastyBird\Core\Security\DI;

use Casbin;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Presenters\Events;
use FastyBird\Core\Security\Access;
use FastyBird\Core\Security\Identity;
use FastyBird\Core\Security\Mapping;
use FastyBird\Core\Security\Middleware;
use FastyBird\Core\Security\Models\Casbin as ModelsCasbin;
use FastyBird\Core\Security\Models\Policies;
use FastyBird\Core\Security\Models\Tokens;
use FastyBird\Core\Security\Services;
use FastyBird\Core\Security\Subscribers;
use Nette\Application;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Nettrine\ORM;
use Override;
use stdClass;
use Symfony\Contracts\EventDispatcher;
use function assert;
use function dirname;
use function is_file;
use function is_string;
use const DIRECTORY_SEPARATOR;

/**
 * Security: authentication and authorization -- tokens, the user and its storage, the access
 * checkers, Casbin, the middlewares, the Doctrine owner mapping and the security entities
 *
 * A child of the composite FastyBird\Core\DI\CoreExtension, which owns and runs it; it is never
 * registered with the compiler itself. It runs under the composite's name and reads its
 * fbCore > simpleAuth section, so its services are fbCore.simpleAuth.*. Nothing is registered
 * unless a token signature is configured. The composite also reads that section, for the root
 * Configuration.
 *
 * In beforeCompile() it maps FastyBird\Core\Security\Entities on the default entity manager,
 * through MappingHelper::of() on this extension.
 */
final class SecurityExtension extends DI\CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema\Schema
	{
		return Schema\Expect::structure([
			// SimpleAuth used to be its own separate, opt-in Nette extension
			// (fbSimpleAuth/SimpleAuthExtension) that a container's own config chose to
			// register -- or not. fbCore is now the single universal extension every
			// container in the repo loads (production and every package's tests alike), so
			// there is no longer a way to simply not register SimpleAuth. An empty-string
			// default keeps container compilation possible for containers that never
			// configure it; everything loadConfiguration() below registers is gated on
			// this same signature being non-empty, so an unconfigured signature never
			// reaches TokenBuilder/TokenValidator or gets used for real token signing -- it
			// simply means none of SimpleAuth's services are registered at all, matching
			// pre-merge behaviour for containers that never opted into fbSimpleAuth.
			'token' => Schema\Expect::structure([
				'issuer' => Schema\Expect::string(),
				'signature' => Schema\Expect::string(''),
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
					dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'resources' . DIRECTORY_SEPARATOR . 'model.conf',
				),
				'policy' => Schema\Expect::string(),
			]),
		]);
	}

	/**
	 * @throws Exceptions\Logic
	 */
	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		if ($configuration->token->signature !== '') {
			$builder->addDefinition($this->prefix('simpleAuth.auth'), new DI\Definitions\ServiceDefinition())
				->setType(Services\Auth::class);

			$builder->addDefinition($this->prefix('simpleAuth.token.builder'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\TokenBuilder::class)
				->setArgument('tokenSignature', $configuration->token->signature)
				->setArgument('tokenIssuer', $configuration->token->issuer);

			$builder->addDefinition($this->prefix('simpleAuth.token.reader'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\TokenReader::class);

			$builder->addDefinition($this->prefix('simpleAuth.token.validator'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\TokenValidator::class)
				->setArgument('tokenSignature', $configuration->token->signature)
				->setArgument('tokenIssuer', $configuration->token->issuer);

			if ($configuration->services->identity) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.security.identityFactory'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Identity\IdentityFactory::class);
			}

			$builder->addDefinition(
				$this->prefix('simpleAuth.security.userStorage'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Identity\UserStorage::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.annotationChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Access\AnnotationChecker::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.latteChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Access\LatteChecker::class);

			$builder->addDefinition(
				$this->prefix('simpleAuth.access.linkChecker'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Access\LinkChecker::class);

			if ($configuration->enable->casbin->database) {
				$adapter = $builder->addDefinition(
					$this->prefix('simpleAuth.casbin.adapter'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(ModelsCasbin\Adapter::class);

				// Adapter::__construct only stores the DBAL connection; every method that
				// actually queries it (loadPolicy, savePolicy, ...) runs later, on demand.
				// Autowiring still resolves the constructor argument eagerly, though, which
				// forces nettrineDbal.connections.default.connection to exist merely because
				// something -- transitively -- asked for an EnforcerFactory. In production
				// (Tracy debug bar + AccountsModule's UserPanel + nettrine/dbal's own Tracy
				// connection panel all present at once) that eager build closes a real cycle:
				// tracy.bar -> fbAccountsModule.security.userPanel -> security.user ->
				// this enforcerFactory -> this adapter -> nettrineDbal's connection, whose own
				// ConnectionPanel::initialize() setup autowires an optional Tracy\Bar argument
				// and calls back into tracy.bar while it is still being constructed. A PHP 8.4
				// lazy ghost defers the constructor (and therefore the connection lookup) until
				// something actually calls a method on the adapter, which happens outside that
				// call stack, breaking the cycle without touching nettrine/dbal or Tracy.
				$adapter->lazy = true;

				$builder->addDefinition(
					$this->prefix('simpleAuth.casbin.subscriber'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Subscribers\Policy::class);
			} else {
				$policyFile = $configuration->casbin->policy;

				if (!is_string($policyFile) || !is_file($policyFile)) {
					throw new Exceptions\Logic('Casbin policy file is not configured');
				}

				$adapter = $builder->addDefinition(
					$this->prefix('simpleAuth.casbin.adapter'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Casbin\Persist\Adapters\FileAdapter::class)
					->setArguments(['filePath' => $policyFile]);
			}

			$modelFile = $configuration->casbin->model;

			if (!is_string($modelFile) || !is_file($modelFile)) {
				throw new Exceptions\Logic('Casbin model file is not configured');
			}

			$builder->addDefinition(
				$this->prefix('simpleAuth.casbin.enforcerFactory'),
				new DI\Definitions\ServiceDefinition(),
			)
				->setType(Identity\EnforcerFactory::class)
				->setArguments(['modelFile' => $modelFile, 'adapter' => $adapter]);

			if ($configuration->enable->middleware) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.middleware.access'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Middleware\Authorization::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.middleware.user'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Middleware\User::class);
			}

			if ($configuration->enable->doctrine->mapping) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.driver'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Mapping\Driver\Owner::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.subscriber'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Subscribers\User::class);
			}

			if ($configuration->enable->doctrine->models) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.tokensRepository'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Tokens\Repository::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.tokensManager'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Tokens\Manager::class);
			}

			if ($configuration->enable->casbin->database) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.policiesRepository'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Policies\Repository::class);

				$builder->addDefinition(
					$this->prefix('simpleAuth.doctrine.policiesManager'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Policies\Manager::class);
			}

			if ($configuration->enable->nette->application) {
				$builder->addDefinition(
					$this->prefix('simpleAuth.nette.application'),
					new DI\Definitions\ServiceDefinition(),
				)
					->setType(Subscribers\Application::class);
			}
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

		$userContextServiceName = $builder->getByType(Identity\User::class);

		// Mirrors the signature !== '' gate around everything in loadConfiguration() above: this
		// fallback's constructor needs IUserStorage, which only exists if that gate passed and
		// registered simpleAuth.security.userStorage. Without this
		// gate, containers that never configure SimpleAuth (signature === '') would still get an
		// unconditional fallback User service whose dependency was never registered, replacing
		// "signature is missing" with a confusing "IUserStorage not found" deep in DI resolution.
		if ($userContextServiceName === null && $configuration->token->signature !== '') {
			$builder->addDefinition($this->prefix('simpleAuth.security.user'), new DI\Definitions\ServiceDefinition())
				->setType(Identity\User::class);
		}

		if (
			$configuration->enable->doctrine->models
			|| $configuration->enable->casbin->database
		) {
			ORM\DI\Helpers\MappingHelper::of($this)->addAttribute(
				'default',
				'FastyBird\Core\Security\Entities',
				dirname(__DIR__) . DIRECTORY_SEPARATOR . 'Entities',
			);
		}

		if ($configuration->enable->nette->application) {
			if (
				$builder->getByType(EventDispatcher\EventDispatcherInterface::class) !== null
				&& $builder->getByType(Application\Application::class) !== null
			) {
				$dispatcher = $builder->getDefinition(
					$builder->getByType(EventDispatcher\EventDispatcherInterface::class),
				);
				$application = $builder->getDefinition($builder->getByType(Application\Application::class));
				assert($application instanceof DI\Definitions\ServiceDefinition);

				$application->addSetup('?->onRequest[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(Events\PresenterRequest::class),
				]);
				$application->addSetup('?->onResponse[] = function() {?->dispatch(new ?(...func_get_args()));}', [
					'@self',
					$dispatcher,
					new PhpGenerator\Literal(Events\PresenterResponse::class),
				]);
			}
		}
	}

}
