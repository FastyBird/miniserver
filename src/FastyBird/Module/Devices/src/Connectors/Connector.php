<?php declare(strict_types = 1);

namespace FastyBird\Module\Devices\Connectors;

use React\Promise;

/**
 * Devices connector interface
 */
interface Connector
{

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function execute(bool $standalone = true): Promise\PromiseInterface;

	/**
	 * @return Promise\PromiseInterface<bool>
	 */
	public function discover(): Promise\PromiseInterface;

	public function terminate(): void;

	public function hasUnfinishedTasks(): bool;

}
