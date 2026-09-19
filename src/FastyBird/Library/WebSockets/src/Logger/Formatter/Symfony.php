<?php declare(strict_types = 1);

namespace FastyBird\Library\WebSockets\Logger\Formatter;

use Symfony\Component\Console;

/**
 * WebSockets server symfony console output formater
 *
 * @package        iPublikuj:WebSockets!
 * @subpackage     Logger
 *
 * @author         Adam Kadlec <adam.kadlec@ipublikuj.eu>
 */
final class Symfony implements IFormatter
{

	public function __construct(private Console\Style\SymfonyStyle $output)
	{
	}

	public function error(string $message): void
	{
		$this->output->error($message);
	}

	public function warning(string $message): void
	{
		$this->output->warning($message);
	}

	public function note(string $message): void
	{
		$this->output->note($message);
	}

	public function caution(string $message): void
	{
		$this->output->caution($message);
	}

}
