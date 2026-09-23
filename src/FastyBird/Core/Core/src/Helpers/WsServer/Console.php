<?php declare(strict_types = 1);

namespace FastyBird\Core\Helpers\WsServer;

use Override;
use Psr\Log;

/**
 * WebSockets server output printer
 */
final class Console implements Log\LoggerInterface
{

	private Formatter\IFormatter $formatter;

	public function setFormatter(Formatter\IFormatter $formatter): void
	{
		$this->formatter = $formatter;
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function emergency($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->caution($message);

		} else {
			echo 'CAUTION! ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function alert($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->error($message);

		} else {
			echo 'ERROR! ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function critical($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->error($message);

		} else {
			echo 'ERROR! ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function error($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->error($message);

		} else {
			echo 'ERROR! ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function warning($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->warning($message);

		} else {
			echo 'WARNING! ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function notice($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->note($message);

		} else {
			echo 'NOTICE! ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function info($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->note($message);

		} else {
			echo 'INFO: ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function debug($message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->note($message);

		} else {
			echo 'DEBUG: ';

			$this->writeln($message);
		}
	}

	/**
	 * {@inheritDoc}
	 */
	#[Override]
	public function log($level, $message, array $context = []): void
	{
		if ($this->formatter) {
			$this->formatter->note($message);

		} else {
			echo 'LOG: ';

			$this->writeln($message);
		}
	}

	private function writeln(string $message): void
	{
		if ($this->formatter) {
			$this->formatter->writeln($message);

		} else {
			echo $message . "\r\n";
		}
	}

}
