import accountsModule from '@fastybird/accounts-module';
import devicesModule from '@fastybird/devices-module';
import homekitConnector from '@fastybird/homekit-connector';

export const extensions = [
	{
		name: '@fastybird/accounts-module',
		module: accountsModule,
	},
	{
		name: '@fastybird/devices-module',
		module: devicesModule,
	},
	{
		name: '@fastybird/homekit-connector',
		module: homekitConnector,
	},
];
