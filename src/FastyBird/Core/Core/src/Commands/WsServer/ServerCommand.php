<?php declare(strict_types = 1);

namespace FastyBird\Core\Commands\WsServer;

use FastyBird\Core\Exceptions;
use FastyBird\Core\Helpers\WsServer as Logger;
use FastyBird\Core\Server\WsServer as Server;
use Psr\Log;
use Symfony\Component\Console;
use Symfony\Component\Console\Input;
use Symfony\Component\Console\Output;
use Symfony\Component\Console\Style;

/**
 * WebSockets server command
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Commands
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
class ServerCommand extends Console\Command\Command
{

	private Log\LoggerInterface|Log\NullLogger|null $logger = null;

	public function __construct(
		private Server\Server $server,
		Log\LoggerInterface|null $logger = null,
		string|null $name = null,
	)
	{
		parent::__construct($name);

		$this->logger = $logger ?? new Log\NullLogger();
	}

	protected function configure(): void
	{
		$this
			->setName('ipub:websockets:start')
			->setDescription('Start WebSocket server.');
	}

	protected function execute(Input\InputInterface $input, Output\OutputInterface $output): int
	{
		$io = new Style\SymfonyStyle($input, $output);

		$io->text([
			'',
			'+------------------+',
			'| WebSocket server |',
			'+------------------+',
			'',
		]);

		if ($this->logger instanceof Logger\Console) {
			$this->logger->setFormatter(new Logger\Formatter\Symfony($io));
		}

		try {
			$this->server->create();
			$this->server->run();

		} catch (Exceptions\Terminate) {
			$this->server->stop();

			return self::FAILURE;
		}

		return self::SUCCESS;
	}

}
