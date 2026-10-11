<?php declare(strict_types = 1);

/**
 * #703 (E7.9, #462): the API change list of the Modbus connector. The format is documented in
 * tools/api-surface.php; the base is the manifest of `main` @ 78c79d57c (--write from a checkout of it).
 *
 * - changed `final` / `readonly`: census T4 and T5;
 * - removed __call(), __callStatic(), __get(), __set(), __isset(), __unset() and changed `traits`:
 *   Nette\SmartObject is no longer used (census T6);
 * - renamed: the trait Clients\TReading to its census T1 role name;
 * - changed `throws` (item 5): API\Rtu::readCoils() and readDiscreteInputs() declare ValueError.
 */

return [
	'renamed' => [
		'FastyBird\\Connector\\Modbus\\Clients\\TReading' => 'FastyBird\\Connector\\Modbus\\Clients\\ReadsRegisters',
	],
	'removed' => [
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager::__call()',
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager::__get()',
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager::__set()',
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Rtu::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Rtu::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Rtu::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Rtu::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Rtu::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Rtu::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Tcp::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Tcp::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Tcp::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Tcp::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Tcp::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Tcp::__unset()',
		'FastyBird\\Connector\\Modbus\\API\\Transformer::__call()',
		'FastyBird\\Connector\\Modbus\\API\\Transformer::__callStatic()',
		'FastyBird\\Connector\\Modbus\\API\\Transformer::__get()',
		'FastyBird\\Connector\\Modbus\\API\\Transformer::__isset()',
		'FastyBird\\Connector\\Modbus\\API\\Transformer::__set()',
		'FastyBird\\Connector\\Modbus\\API\\Transformer::__unset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress::__call()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress::__get()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress::__isset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress::__set()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress::__unset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read::__call()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read::__get()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read::__isset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read::__set()',
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read::__unset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu::__call()',
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu::__get()',
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu::__isset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu::__set()',
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu::__unset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp::__call()',
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp::__get()',
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp::__isset()',
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp::__set()',
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp::__unset()',
		'FastyBird\\Connector\\Modbus\\Connector\\Connector::__call()',
		'FastyBird\\Connector\\Modbus\\Connector\\Connector::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Connector\\Connector::__get()',
		'FastyBird\\Connector\\Modbus\\Connector\\Connector::__isset()',
		'FastyBird\\Connector\\Modbus\\Connector\\Connector::__set()',
		'FastyBird\\Connector\\Modbus\\Connector\\Connector::__unset()',
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel::__call()',
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel::__get()',
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel::__isset()',
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel::__set()',
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel::__unset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers::__call()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers::__get()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers::__isset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers::__set()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers::__unset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState::__call()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState::__get()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState::__set()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState::__call()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState::__get()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState::__isset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState::__set()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState::__unset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState::__call()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState::__get()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState::__isset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState::__set()',
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState::__unset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Queue::__call()',
		'FastyBird\\Connector\\Modbus\\Queue\\Queue::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Queue\\Queue::__get()',
		'FastyBird\\Connector\\Modbus\\Queue\\Queue::__isset()',
		'FastyBird\\Connector\\Modbus\\Queue\\Queue::__set()',
		'FastyBird\\Connector\\Modbus\\Queue\\Queue::__unset()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls::__call()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls::__get()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls::__isset()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls::__set()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls::__unset()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties::__call()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties::__get()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties::__isset()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties::__set()',
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties::__unset()',
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData::__call()',
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData::__callStatic()',
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData::__get()',
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData::__isset()',
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData::__set()',
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData::__unset()',
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::__call()',
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::__callStatic()',
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::__get()',
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::__isset()',
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::__set()',
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic::__unset()',
	],
	'changed' => [
		'FastyBird\\Connector\\Modbus\\API\\ConnectionManager' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Interfaces\\Configuration' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadAnalogInputs' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\ReadDigitalInputs' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteCoil' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteHoldingRegister' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Messages\\Response\\WriteMultiple' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Rtu::readCoils()' => [
			'throws' => [
				'FastyBird\\Connector\\Modbus\\Exceptions\\InvalidState',
				'FastyBird\\Connector\\Modbus\\Exceptions\\ModbusRtu',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\Modbus\\API\\Rtu::readDiscreteInputs()' => [
			'throws' => [
				'FastyBird\\Connector\\Modbus\\Exceptions\\InvalidState',
				'FastyBird\\Connector\\Modbus\\Exceptions\\ModbusRtu',
				'ValueError',
			],
		],
		'FastyBird\\Connector\\Modbus\\API\\Rtu' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Tcp' => [
			'final' => true,
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\API\\Transformer' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Pointer\\ReadAddress' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Clients\\Messages\\Request\\Read' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Clients\\Rtu' => [
			'final' => true,
			'traits' => [
				'FastyBird\\Connector\\Modbus\\Clients\\ReadsRegisters',
			],
		],
		'FastyBird\\Connector\\Modbus\\Clients\\Tcp' => [
			'final' => true,
			'traits' => [
				'FastyBird\\Connector\\Modbus\\Clients\\ReadsRegisters',
			],
		],
		'FastyBird\\Connector\\Modbus\\Commands\\Execute' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Commands\\Install' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Connector\\Connector' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\DI\\ModbusExtension' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Documents\\Channels\\Channel' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Documents\\Connectors\\Connector' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Documents\\Devices\\Device' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\InvalidArgument' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\InvalidState' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\ModbusRtu' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\ModbusTcp' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\NotReachable' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\NotSupported' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\Runtime' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Exceptions\\Timeout' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Helpers\\Channel' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindChannelDynamicProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindChannelVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindConnectorVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindDeviceVariableProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Configuration\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindChannelDynamicProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindChannelProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindChannels' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindConnectorProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindConnectors' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindDeviceProperties' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queries\\Entities\\FindDevices' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\StoreDeviceConnectionState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Queue\\Consumers\\WriteChannelPropertyState' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Queue\\Messages\\State' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Queue\\Queue' => [
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Subscribers\\Controls' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Subscribers\\Properties' => [
			'readonly' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\ValueObjects\\DeviceData' => [
			'final' => true,
			'traits' => [],
		],
		'FastyBird\\Connector\\Modbus\\Writers\\Event' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Writers\\Exchange' => [
			'final' => true,
		],
		'FastyBird\\Connector\\Modbus\\Writers\\Periodic' => [
			'traits' => [],
		],
	],
];
