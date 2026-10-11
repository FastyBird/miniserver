<?php declare(strict_types = 1);

namespace FastyBird\Connector\NsPanel\API;

use Ramsey\Uuid;

/**
 * NS Panel LAN API interface factory
 */
interface LanApiFactory
{

	public function create(Uuid\UuidInterface $id): LanApi;

}
