<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API;

/**
 * Generation 1 device http API factory
 */
interface Gen1HttpApiFactory
{

	public function create(): Gen1HttpApi;

}
