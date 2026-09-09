<?php declare(strict_types = 1);

/**
 * WsServerExtension.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     DI
 * @since          1.0.0
 *
 * @date           22.02.21
 */

namespace FastyBird\Plugin\WsServer\DI;

use FastyBird\Core\Application\Boot as ApplicationBoot;
use FastyBird\Core\Exchange\Exchange as ExchangeExchange;
use FastyBird\Plugin\WsServer\Commands;
use FastyBird\Plugin\WsServer\Events;
use FastyBird\Plugin\WsServer\Exceptions;
use FastyBird\Plugin\WsServer\Subscribers;
use IPub\WebSockets;
use Nette;
use Nette\Bootstrap;
use Nette\DI;
use Nette\PhpGenerator;
use Nette\Schema;
use Psr\EventDispatcher;
use stdClass;
use function assert;
use function is_string;
use function sprintf;

/**
 * WS server plugin
 *
 * @package        FastyBird:WsServerPlugin!
 * @subpackage     DI
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class WsServerExtension extends DI\CompilerExtension
{

	public const NAME = 'fbWsServerPlugin';

	public static function register(
		ApplicationBoot\Configurator $config,
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
			'access' => Schema\Expect::structure([
				'keys' => Schema\Expect::string()->default(null),
				'origins' => Schema\Expect::string()->default(null),
			]),
		]);
	}

	/**
	 * @throws Nette\DI\NotAllowedDuringResolvingException
	 */
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$configuration = $this->getConfig();
		assert($configuration instanceof stdClass);

		$builder->addDefinition($this->prefix('commands.server'), new DI\Definitions\ServiceDefinition())
			->setType(Commands\WsServer::class)
			->setArguments([
				'exchangeFactories' => $builder->findByType(ExchangeExchange\Factory::class),
			]);

		$builder->addDefinition($this->prefix('subscribers.client'), new DI\Definitions\ServiceDefinition())
			->setType(Subscribers\Client::class)
			->setArgument('wsKeys', $configuration->access->keys)
			->setArgument('allowedOrigins', $configuration->access->origins);
	}

	/**
	 * @throws Exceptions\Logic
	 * @throws Nette\DI\MissingServiceException
	 * @throws Nette\DI\NotAllowedDuringResolvingException
	 */
	public function beforeCompile(): void
	{
		parent::beforeCompile();

		$builder = $this->getContainerBuilder();

		/**
		 * Events bridges
		 */

		if ($builder->getByType(EventDispatcher\EventDispatcherInterface::class) === null) {
			throw new Exceptions\Logic(
				sprintf(
					'Service of type "%s" is needed. Please register it.',
					EventDispatcher\EventDispatcherInterface::class,
				),
			);
		}

		$dispatcher = $builder->getDefinition($builder->getByType(EventDispatcher\EventDispatcherInterface::class));

		$socketWrapperServiceName = $builder->getByType(WebSockets\Server\Wrapper::class);
		assert(is_string($socketWrapperServiceName));

		$socketWrapperService = $builder->getDefinition($socketWrapperServiceName);
		assert($socketWrapperService instanceof DI\Definitions\ServiceDefinition);

		$socketWrapperService->addSetup(
			'?->onClientConnected[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self',
				$dispatcher,
				new PhpGenerator\Literal(Events\ClientConnected::class),
			],
		);

		$socketWrapperService->addSetup(
			'?->onIncomingMessage[] = function() {?->dispatch(new ?(...func_get_args()));}',
			[
				'@self',
				$dispatcher,
				new PhpGenerator\Literal(Events\IncomingMessage::class),
			],
		);
	}

}
