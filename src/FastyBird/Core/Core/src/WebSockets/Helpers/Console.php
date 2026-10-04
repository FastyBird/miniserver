<?php declare(strict_types = 1);

namespace FastyBird\Core\WebSockets\Helpers;

use Override;
use Psr\Log;

/**
 * WebSockets server output printer
 */
final class Console implements Log\LoggerInterface
{

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function emergency($message, array $context = []): void
	{
		echo 'CAUTION! ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function alert($message, array $context = []): void
	{
		echo 'ERROR! ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function critical($message, array $context = []): void
	{
		echo 'ERROR! ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function error($message, array $context = []): void
	{
		echo 'ERROR! ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function warning($message, array $context = []): void
	{
		echo 'WARNING! ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function notice($message, array $context = []): void
	{
		echo 'NOTICE! ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function info($message, array $context = []): void
	{
		echo 'INFO: ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function debug($message, array $context = []): void
	{
		echo 'DEBUG: ';

		$this->writeln($message);
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function log($level, $message, array $context = []): void
	{
		echo 'LOG: ';

		$this->writeln($message);
	}

	private function writeln(string $message): void
	{
		echo $message . "\r\n";
	}

}
