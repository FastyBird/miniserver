<?php declare(strict_types = 1);

namespace FastyBird\Connector\FbMqtt\Clients;

use BinSoul\Net\Mqtt;
use Override;
use React\Promise;

/**
 * Decorates flows with data required for the {@see Client} class.
 */
class Flow implements Mqtt\Flow
{

	/**
	 * @param Promise\Deferred<mixed> $deferred
	 */
	public function __construct(
		private readonly Mqtt\Flow $decorated,
		private readonly Promise\Deferred $deferred,
		private Mqtt\Packet|null $packet = null,
		private readonly bool $isSilent = false,
	)
	{
	}

	#[Override]
	public function getCode(): string
	{
		return $this->decorated->getCode();
	}

	#[Override]
	public function start(): Mqtt\Packet|null
	{
		$this->packet = $this->decorated->start();

		return $this->packet;
	}

	#[Override]
	public function accept(Mqtt\Packet $packet): bool
	{
		return $this->decorated->accept($packet);
	}

	#[Override]
	public function next(Mqtt\Packet $packet): Mqtt\Packet|null
	{
		$this->packet = $this->decorated->next($packet);

		return $this->packet;
	}

	#[Override]
	public function isFinished(): bool
	{
		return $this->decorated->isFinished();
	}

	#[Override]
	public function isSuccess(): bool
	{
		return $this->decorated->isSuccess();
	}

	#[Override]
	public function getResult()
	{
		return $this->decorated->getResult();
	}

	#[Override]
	public function getErrorMessage(): string
	{
		return $this->decorated->getErrorMessage();
	}

	/**
	 * Returns the associated deferred.
	 *
	 * @return Promise\Deferred<mixed>
	 */
	public function getDeferred(): Promise\Deferred
	{
		return $this->deferred;
	}

	/**
	 * Returns the current packet.
	 */
	public function getPacket(): Mqtt\Packet|null
	{
		return $this->packet;
	}

	/**
	 * Indicates if the flow should emit events.
	 */
	public function isSilent(): bool
	{
		return $this->isSilent;
	}

}
