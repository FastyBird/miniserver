<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API;

/**
 * Generation 2 device http API factory
 */
interface Gen2HttpApiFactory
{

	public function create(): Gen2HttpApi;

}
