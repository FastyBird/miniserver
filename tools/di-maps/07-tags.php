<?php declare(strict_types = 1);

/**
 * E4.7 (#559) DI tag map, from the E4.1 census (#553): today => target.
 *
 * Census section 4 (docs/superpowers/plans/2026-09-27-core-e4-di-census.md) verbatim, in the
 * format `php tools/di-snapshot.php --diff <base> <head> --map <this file>` reads. 5 rows, all of
 * which change. --map applies it to the base side. `nette.inject` is Nette's and is not mapped.
 */
return [
	'tags' => [
		'fastybird.application.attribute.driver' => 'fastybird.core.documents.attributeDriver',
		'consumer_state' => 'fastybird.core.exchange.consumerState',
		'consumer_routing_key' => 'fastybird.core.exchange.consumerRoutingKey',
		'ipub.websockets.routes' => 'fastybird.core.webSockets.routes',
		'ipub.websockets.controller' => 'fastybird.core.webSockets.controller',
	],
];
