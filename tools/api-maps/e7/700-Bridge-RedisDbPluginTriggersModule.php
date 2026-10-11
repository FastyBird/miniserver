<?php declare(strict_types = 1);

/**
 * #700 (E7.6, #462): the consumer sweep into Bridge/RedisDbPluginTriggersModule. Its state classes implement the
 * module's renamed state-store contracts (census T1), so only their `interfaces` lists change.
 * The format is documented in tools/api-surface.php; the base is the manifest of `main` @ 746772d5e.
 */

return [
	'changed' => [
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Triggers\\Models\\States\\ActionsWriter',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ActionsRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Triggers\\Models\\States\\ActionsReader',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsManager' => [
			'interfaces' => [
				'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsWriter',
			],
		],
		'FastyBird\\Bridge\\RedisDbPluginTriggersModule\\Models\\States\\ConditionsRepository' => [
			'interfaces' => [
				'FastyBird\\Module\\Triggers\\Models\\States\\ConditionsReader',
			],
		],
	],
];
