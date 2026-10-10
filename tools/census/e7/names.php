<?php declare(strict_types = 1);

/**
 * E7.0 census (#694), T1: the PROPOSED role name of every `I`-interface and `T`-trait in the 28
 * packages (#462 §3 D5). census.php t1 prints these next to the measured facts, and checks that
 * the proposed name is itself free in its namespace and its package.
 *
 * Nothing here is approved until the maintainer approves the census at the E7.0 checkpoint
 * (#462 §11). A row marked OPEN has no single sensible name; it lists the alternatives and the
 * maintainer picks one.
 *
 * Naming rules applied (docs/conventions.md, Naming): no prefix and no suffix; never a
 * mechanical drop of the prefix, because the bare name already exists beside each one (or is a
 * reserved word); a trait names the capability it gives its user. Core's own precedent:
 * an interface `X` with its default implementation in a trait `HasX` (`Documents\Owner` /
 * `Documents\HasOwner`, `Persistence\Entities\EntityCreated` / `HasEntityCreated`).
 *
 * FQCN => [proposed short name, role and reason, OPEN?]
 *
 * @return array<string, array{0: string, 1: string, 2?: bool}>
 */

$stateReader = 'The storage-backend contract the module reads a state through; its only implementation is the RedisDb bridge (K2) and the module resolves it by type (K3). The module\'s own caching facade beside it is already `%s`, so the contract is named for its side of the split: it reads.';
$stateWriter = 'The storage-backend contract the module writes a state through (create/update/delete); implemented only by the RedisDb bridge (K2), resolved by type (K3). The facade beside it is already `%s`; the contract is named for what it does: it writes.';
$finder = 'Gives a controller `%s()`: load the entity named by the request\'s URL id or answer 404. Named for the capability. A bare `Finders\\%s` would read as the entity `Entities\\…\\%s` the same controllers import.';
$hasParams = 'The default implementation of the `Entities\\EntityParams` interface beside it (the JSON `params` column and its accessors). Core\'s pattern: interface `X`, trait `HasX` (`Persistence\\Entities\\EntityCreated` / `HasEntityCreated`). A bare drop collides with that interface.';
$hasId = 'Gives an entity `getId()`%s, part of the package\'s `Entities\\Entity` contract. A bare drop collides with that interface. Alternatives: `HasId`, `IdentifiesEntity`.';
$parameter = 'The default implementation of the display-parameter interface `Parameters\\%s` beside it (the property and its `get…()`/`set…()` through the entity params). Core\'s `X` / `HasX` pattern; a bare drop collides with that interface.';
$loads = 'Gives a presenter `load%1$s()` and `load%2$s()`, which put the %3$s documents into the template. A bare `Presenters\\%1$s` would read as a presenter class next to `%1$sPresenter`.';

return [
	// ---- Module/Devices: the 12 state-store contracts (K2 + K3) --------------------------------
	'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\IRepository' => ['Reader', sprintf($stateReader, 'Connectors\\Repository')],
	'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\IManager' => ['Writer', sprintf($stateWriter, 'Connectors\\Manager')],
	'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\Async\\IRepository' => ['Reader', sprintf($stateReader, 'Connectors\\Async\\Repository') . ' Async: the methods return promises.'],
	'FastyBird\\Module\\Devices\\Models\\States\\Connectors\\Async\\IManager' => ['Writer', sprintf($stateWriter, 'Connectors\\Async\\Manager') . ' Async: the methods return promises.'],
	'FastyBird\\Module\\Devices\\Models\\States\\Devices\\IRepository' => ['Reader', sprintf($stateReader, 'Devices\\Repository')],
	'FastyBird\\Module\\Devices\\Models\\States\\Devices\\IManager' => ['Writer', sprintf($stateWriter, 'Devices\\Manager')],
	'FastyBird\\Module\\Devices\\Models\\States\\Devices\\Async\\IRepository' => ['Reader', sprintf($stateReader, 'Devices\\Async\\Repository') . ' Async: the methods return promises.'],
	'FastyBird\\Module\\Devices\\Models\\States\\Devices\\Async\\IManager' => ['Writer', sprintf($stateWriter, 'Devices\\Async\\Manager') . ' Async: the methods return promises.'],
	'FastyBird\\Module\\Devices\\Models\\States\\Channels\\IRepository' => ['Reader', sprintf($stateReader, 'Channels\\Repository')],
	'FastyBird\\Module\\Devices\\Models\\States\\Channels\\IManager' => ['Writer', sprintf($stateWriter, 'Channels\\Manager')],
	'FastyBird\\Module\\Devices\\Models\\States\\Channels\\Async\\IRepository' => ['Reader', sprintf($stateReader, 'Channels\\Async\\Repository') . ' Async: the methods return promises.'],
	'FastyBird\\Module\\Devices\\Models\\States\\Channels\\Async\\IManager' => ['Writer', sprintf($stateWriter, 'Channels\\Async\\Manager') . ' Async: the methods return promises.'],

	// ---- Module/Triggers: the 4 state-store contracts (K2 + K3) --------------------------------
	'FastyBird\\Module\\Triggers\\Models\\States\\IActionsRepository' => ['ActionsReader', sprintf($stateReader, 'ActionsRepository') . ' Same split as Devices\' `Reader`/`Writer`; the plural stays because the namespace holds actions and conditions side by side.'],
	'FastyBird\\Module\\Triggers\\Models\\States\\IActionsManager' => ['ActionsWriter', sprintf($stateWriter, 'ActionsManager')],
	'FastyBird\\Module\\Triggers\\Models\\States\\IConditionsRepository' => ['ConditionsReader', sprintf($stateReader, 'ConditionsRepository')],
	'FastyBird\\Module\\Triggers\\Models\\States\\IConditionsManager' => ['ConditionsWriter', sprintf($stateWriter, 'ConditionsManager')],

	// ---- Controllers\Finders: one trait per looked-up entity -----------------------------------
	'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TAccount' => ['FindsAccount', sprintf($finder, 'findAccount', 'Account', 'Account')],
	'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TEmail' => ['FindsEmail', sprintf($finder, 'findEmail', 'Email', 'Email')],
	'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TIdentity' => ['FindsIdentity', sprintf($finder, 'findIdentity', 'Identity', 'Identity')],
	'FastyBird\\Module\\Accounts\\Controllers\\Finders\\TRole' => ['FindsRole', sprintf($finder, 'findRole', 'Role', 'Role')],
	'FastyBird\\Module\\Devices\\Controllers\\Finders\\TConnector' => ['FindsConnector', sprintf($finder, 'findConnector', 'Connector', 'Connector')],
	'FastyBird\\Module\\Devices\\Controllers\\Finders\\TDevice' => ['FindsDevice', sprintf($finder, 'findDevice', 'Device', 'Device')],
	'FastyBird\\Module\\Devices\\Controllers\\Finders\\TChannel' => ['FindsChannel', sprintf($finder, 'findChannel', 'Channel', 'Channel')],
	'FastyBird\\Module\\Devices\\Controllers\\Finders\\TDeviceProperty' => ['FindsDeviceProperty', sprintf($finder, 'findProperty', 'DeviceProperty', 'Property') . ' Its method is the generic `findProperty()`; the trait name says which property.'],
	'FastyBird\\Module\\Devices\\Controllers\\Finders\\TChannelProperty' => ['FindsChannelProperty', sprintf($finder, 'findProperty', 'ChannelProperty', 'Property') . ' Its method is the generic `findProperty()`; the trait name says which property.'],
	'FastyBird\\Module\\Triggers\\Controllers\\Finders\\TTrigger' => ['FindsTrigger', sprintf($finder, 'findTrigger', 'Trigger', 'Trigger')],
	'FastyBird\\Module\\Ui\\Controllers\\Finders\\TDashboard' => ['FindsDashboard', sprintf($finder, 'findDashboard', 'Dashboard', 'Dashboard')],
	'FastyBird\\Module\\Ui\\Controllers\\Finders\\TTab' => ['FindsTab', sprintf($finder, 'findTab', 'Tab', 'Tab')],
	'FastyBird\\Module\\Ui\\Controllers\\Finders\\TGroup' => ['FindsGroup', sprintf($finder, 'findGroup', 'Group', 'Group')],
	'FastyBird\\Module\\Ui\\Controllers\\Finders\\TWidget' => ['FindsWidget', sprintf($finder, 'findWidget', 'Widget', 'Widget')],

	// ---- Entities\TEntity / TEntityParams (Doctrine entity traits: mapping must not change) ----
	'FastyBird\\Module\\Accounts\\Entities\\TEntity' => ['HasEntityId', sprintf($hasId, ' and `getSource()` (the module\'s source)')],
	'FastyBird\\Module\\Devices\\Entities\\TEntity' => ['HasEntityId', sprintf($hasId, '')],
	'FastyBird\\Module\\Triggers\\Entities\\TEntity' => ['HasEntityId', sprintf($hasId, ', `getPlainId()` and `getSource()`')],
	'FastyBird\\Module\\Ui\\Entities\\TEntity' => ['HasEntityId', sprintf($hasId, '')],
	'FastyBird\\Plugin\\ApiKey\\Entities\\TEntity' => ['HasEntityId', sprintf($hasId, '') . ' Its one user is `Entities\\Key`, into which D6 collapses `Entities\\Entity` (T2); E7.2 may inline the trait into `Key` instead of renaming it.'],
	'FastyBird\\Module\\Accounts\\Entities\\TEntityParams' => ['HasEntityParams', $hasParams],
	'FastyBird\\Module\\Devices\\Entities\\TEntityParams' => ['HasEntityParams', $hasParams],
	'FastyBird\\Module\\Triggers\\Entities\\TEntityParams' => ['HasEntityParams', $hasParams . ' Triggers\' `EntityParams` interface is itself OPEN in T2 (R1).'],
	'FastyBird\\Module\\Ui\\Entities\\TEntityParams' => ['HasEntityParams', $hasParams],

	// ---- Module/Accounts hydrators ---------------------------------------------------------------
	'FastyBird\\Module\\Accounts\\Hydrators\\Accounts\\TAccount' => ['HydratesAccount', 'The attribute hydration shared by `Hydrators\\Accounts\\Account` and `ProfileAccount` (`hydrateFirstNameAttribute()` …, `getEntityName()`). A bare drop collides with the concrete `Account` hydrator in the same namespace.'],
	'FastyBird\\Module\\Accounts\\Hydrators\\Emails\\TEmail' => ['HydratesEmail', 'The attribute hydration shared by `Hydrators\\Emails\\Email` and `ProfileEmail`. A bare drop collides with the concrete `Email` hydrator in the same namespace.'],

	// ---- Module/Devices presenters ---------------------------------------------------------------
	'FastyBird\\Module\\Devices\\Presenters\\TConnectors' => ['LoadsConnectors', sprintf($loads, 'Connectors', 'Connector', 'connector')],
	'FastyBird\\Module\\Devices\\Presenters\\TDevices' => ['LoadsDevices', sprintf($loads, 'Devices', 'Device', 'device')],
	'FastyBird\\Module\\Devices\\Presenters\\TChannels' => ['LoadsChannels', sprintf($loads, 'Channels', 'Channel', 'channel')],

	// ---- Module/Ui display parameters (Doctrine entity traits) ----------------------------------
	'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TIcon' => ['HasIcon', sprintf($parameter, 'Icon')],
	'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TMinimumValue' => ['HasMinimumValue', sprintf($parameter, 'MinimumValue')],
	'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TMaximumValue' => ['HasMaximumValue', sprintf($parameter, 'MaximumValue')],
	'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TPrecision' => ['HasPrecision', sprintf($parameter, 'Precision')],
	'FastyBird\\Module\\Ui\\Entities\\Widgets\\Displays\\Parameters\\TStepValue' => ['HasStepValue', sprintf($parameter, 'StepValue')],

	// ---- Connectors -------------------------------------------------------------------------------
	'FastyBird\\Connector\\FbMqtt\\Queue\\Consumers\\TProperty' => ['HandlesPropertyConfiguration', 'Gives the `ChannelProperty` and `DeviceProperty` consumers `handlePropertyConfiguration()`, which turns a property message\'s attributes into the values to store. A bare `Consumers\\Property` would read as a third consumer beside them, and `Queue\\Messages\\Property` already exists.'],
	'FastyBird\\Connector\\Modbus\\Clients\\TReading' => ['ReadsRegisters', 'The register-reading logic shared by the `Rtu` and `Tcp` clients: `split()` a read into requests, `processDigitalRegistersResponse()`, `processAnalogRegistersResponse()`. A bare `Clients\\Reading` would read as a client class.'],
	'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\TDevice' => ['MapsDeviceStates', 'Gives a `Uiid…` message `toStates()` for the device-level parameters (status LED, firmware, SSID, RSSI). A bare `Uiid\\Device` would read as a device message, beside 7 other `Device` types in the package.'],
	'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\TSwitch' => ['MapsSwitchStates', 'Gives a single-outlet `Uiid…` message `toStates()` for its switch. A bare drop is impossible: `switch` is a reserved word. `Uiid\\SwitchState`, the state object, already exists.'],
	'FastyBird\\Connector\\Sonoff\\API\\Messages\\Uiid\\TSwitches' => ['MapsMultiSwitchStates', 'Gives a multi-outlet `Uiid…` message `toStates()` for every switch, its configuration and pulse. Named apart from `MapsSwitchStates` by what differs: several outlets.'],
];
