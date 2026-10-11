<?php declare(strict_types = 1);

namespace FastyBird\Connector\Shelly\API;

/**
 * Generation 1 device CoAP factory
 */
interface Gen1CoapFactory
{

	public function create(): Gen1Coap;

}
