<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Helpers\Formatter;

use Override;
use Symfony\Component\Console;

/**
 * WebSockets server symfony console output formater
 */
final class Symfony implements IFormatter
{

	public function __construct(private Console\Style\SymfonyStyle $output)
	{
	}

	#[Override]
	public function error(string $message): void
	{
		$this->output->error($message);
	}

	#[Override]
	public function warning(string $message): void
	{
		$this->output->warning($message);
	}

	#[Override]
	public function note(string $message): void
	{
		$this->output->note($message);
	}

	#[Override]
	public function caution(string $message): void
	{
		$this->output->caution($message);
	}

}
