<?php declare(strict_types = 1);

/**
 * HttpServer.php
 *
 * @license        More in LICENSE.md
 * @copyright      https://www.fastybird.com
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Commands
 * @since          1.0.0
 *
 * @date           15.03.20
 */

namespace FastyBird\Core\Commands;

use FastyBird\Core\Events\HttpServer as Events;
use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers\Tools as ToolsHelpers;
use FastyBird\Core\Server\HttpServer as Server;
use FastyBird\Core\Types\Metadata as MetadataTypes;
use Nette;
use Psr\EventDispatcher;
use Psr\Log;
use React\EventLoop;
use React\Socket;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Throwable;
use function file_exists;
use function is_file;

/**
 * HTTP server command
 *
 * @package        FastyBird:WebServerPlugin!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@fastybird.com>
 */
class HttpServer extends Console\Command\Command
{

	use Nette\SmartObject;

	public const NAME = 'fb:web-server:start';

	public function __construct(
		private readonly string $serverAddress,
		private readonly int $serverPort,
		private readonly Server\Factory $serverFactory,
		private readonly EventLoop\LoopInterface $eventLoop,
		private readonly EventDispatcher\EventDispatcherInterface|null $dispatcher = null,
		private readonly Log\LoggerInterface $logger = new Log\NullLogger(),
		private readonly string|null $serverCertificate = null,
		string|null $name = null,
	)
	{
		parent::__construct($name);
	}

	/**
	 * @throws Console\Exception\InvalidArgumentException
	 */
	protected function configure(): void
	{
		parent::configure();

		$this
			->setName(self::NAME)
			->setDescription('Start http server');
	}

	protected function execute(
		Input\InputInterface $input,
		Output\OutputInterface $output,
	): int
	{
		$this->logger->info(
			'Starting HTTP Server',
			[
				'source' => MetadataTypes\Sources\Plugin::WEB_SERVER->value,
				'type' => 'server-command',
			],
		);

		try {
			$this->dispatcher?->dispatch(new Events\Startup());

			$socketServer = new Socket\SocketServer(
				$this->serverAddress . ':' . $this->serverPort,
				[],
				$this->eventLoop,
			);

			if ($this->serverCertificate !== null) {
				if (
					is_file($this->serverCertificate)
					&& file_exists($this->serverCertificate)
				) {
					$socketServer = new Socket\SecureServer($socketServer, $this->eventLoop, [
						'local_cert' => $this->serverCertificate,
					]);

				} else {
					throw new Exceptions\InvalidArgument('Provided SSL certificate file could not be loaded');
				}
			}

			$socketServer->on('error', function (Throwable $ex): void {
				$this->dispatcher?->dispatch(new Events\Error($ex));
			});

			$this->serverFactory->create($socketServer);

			$this->eventLoop->run();

		} catch (Throwable $ex) {
			// Log error action reason
			$this->logger->error(
				'An unhandled error occurred. Stopping HTTP server',
				[
					'source' => MetadataTypes\Sources\Plugin::WEB_SERVER->value,
					'type' => 'server-command',
					'exception' => ToolsHelpers\Logger::buildException($ex),
					'cmd' => $this->getName(),
				],
			);

			$this->eventLoop->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
