<?php declare(strict_types = 1);

namespace FastyBird\Connector\Sonoff\API;

/**
 * Local LAN API factory
 */
interface LanApiFactory
{

	public function create(): LanApi;

}
