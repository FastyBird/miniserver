import { DataType } from '@fastybird/miniserver-core';

import { IChannel, IChannelProperty, IConnector, IConnectorProperty, IDevice, IDeviceProperty } from '../types';

export enum FormResultTypes {
	NONE = 'none',
	WORKING = 'working',
	ERROR = 'error',
	OK = 'ok',
}

export type FormResultType = FormResultTypes.NONE | FormResultTypes.WORKING | FormResultTypes.ERROR | FormResultTypes.OK;

export interface IConnectorForm {
	details: {
		identifier?: IConnector['identifier'];
		name: IConnector['name'];
		comment: IConnector['comment'];
	};
	properties?: {
		variable?: { [key: IConnectorProperty['id']]: string | number | boolean | Date | null };
	};
}

export interface IDeviceForm {
	details: {
		identifier?: IDevice['identifier'];
		name: IDevice['name'];
		comment: IDevice['comment'];
	};
	properties?: {
		variable?: { [key: IDeviceProperty['id']]: string | number | boolean | Date | null };
	};
}

export interface IChannelForm {
	details: {
		identifier?: IChannel['identifier'];
		name: IChannel['name'];
		comment: IChannel['comment'];
	};
	properties?: {
		variable?: { [key: IChannelProperty['id']]: string | number | boolean | Date | null };
	};
}

export interface IPropertyForm {
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	invalid: string | null;
	scale: number | null;
	format: string | null;
}
