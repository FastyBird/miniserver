<?php declare(strict_types = 1);

namespace FastyBird\Core\EventLoop\Application;

/**
 * Event loop status helper
 */
final class Status
{

	private bool $status = false;

	public function setStatus(bool $status): void
	{
		$this->status = $status;
	}

	public function isRunning(): bool
	{
		return $this->status;
	}

}
