import { ButtonPayload, CoverPayload, DataType, SwitchPayload } from '@fastybird/miniserver-core';

import {
	IChannel,
	IChannelControl,
	IChannelProperty,
	IConnector,
	IConnectorControl,
	IConnectorProperty,
	IDevice,
	IDeviceControl,
	IDeviceProperty,
} from '../models/types';

export * from '../components/types';
export * from '../composables/types';
export * from '../models/types';
export * from '../views/types';
export * from './connector';
export * from './components';
export * from './exchange';
export * from './forms';

export interface IDevicesModuleMeta {
	author: string;
	website: string;
	version: string;
	[key: string]: any;
}

export interface IConnectorData {
	connector: IConnector;
	properties: IConnectorProperty[];
	controls: IConnectorControl[];
	devices: IDeviceData[];
}

export interface IDeviceData {
	connector: IConnector | null;
	device: IDevice;
	properties: IDeviceProperty[];
	controls: IDeviceControl[];
	channels: IChannelData[];
}

export interface IChannelData {
	device: IDevice | null;
	channel: IChannel;
	properties: IChannelProperty[];
	controls: IChannelControl[];
}

export interface IRoutes {
	root: string;

	devices: string;
	deviceCreate: string;
	deviceDetail: string;
	deviceSettings: string;

	channelCreate: string;
	channelDetail: string;
	channelSettings: string;

	plugins: string;
	pluginInstall: string;
	pluginDetail: string;

	connectorCreate: string;
	connectorDetail: string;
	connectorSettings: string;

	connectorDetailDeviceCreate: string;
	connectorDetailDeviceDetail: string;
	connectorDetailDeviceSettings: string;
	connectorDetailDeviceDetailChannelCreate: string;
	connectorDetailDeviceDetailChannelDetail: string;
	connectorDetailDeviceDetailChannelSettings: string;
}

export enum PropertyType {
	DYNAMIC = 'dynamic',
	VARIABLE = 'variable',
	MAPPED = 'mapped',
}

export interface IBridge {
	any: any;
}

export interface IDebugLog {
	type: 'debug' | 'info' | 'warning' | 'error';
}

export interface IService {
	running: boolean;
}

export enum ConnectorPropertyIdentifier {
	STATE = 'state',
	SERVER = 'server',
	PORT = 'port',
	SECURED_PORT = 'secured_port',
	BAUD_RATE = 'baud_rate',
	INTERFACE = 'interface',
	ADDRESS = 'address',
}

export enum DevicePropertyIdentifier {
	STATE = 'state',
	BATTERY = 'battery',
	WIFI = 'wifi',
	SIGNAL = 'signal',
	RSSI = 'rssi',
	SSID = 'ssid',
	VCC = 'vcc',
	CPU_LOAD = 'cpu_load',
	UPTIME = 'uptime',
	ADDRESS = 'address',
	IP_ADDRESS = 'ip_address',
	DOMAIN = 'domain',
	STATUS_LED = 'status_led',
	FREE_HEAP = 'free_heap',
	HARDWARE_MANUFACTURER = 'hardware_manufacturer',
	HARDWARE_MODEL = 'hardware_model',
	HARDWARE_VERSION = 'hardware_version',
	HARDWARE_MAC_ADDRESS = 'hardware_mac_address',
	FIRMWARE_MANUFACTURER = 'firmware_manufacturer',
	FIRMWARE_NAME = 'firmware_name',
	FIRMWARE_VERSION = 'firmware_version',
	SERIAL_NUMBER = 'serial_number',
	STATE_READING_DELAY = 'state_reading_delay',
	STATE_PROCESSING_DELAY = 'state_processing_delay',
}

export enum ChannelPropertyIdentifier {
	ADDRESS = 'address',
}

export type StateColor = 'info' | 'warning' | 'success' | 'primary' | 'danger' | undefined;

export enum SimpleStateFilter {
	ALL = 'all',
	ONLINE = 'online',
	OFFLINE = 'offline',
}

export enum ConnectionState {
	CONNECTED = 'connected',
	DISCONNECTED = 'disconnected',
	INIT = 'init',
	READY = 'ready',
	RUNNING = 'running',
	SLEEPING = 'sleeping',
	STOPPED = 'stopped',
	LOST = 'lost',
	ALERT = 'alert',
	UNKNOWN = 'unknown',
}

export enum ConnectorCategory {
	GENERIC = 'generic',
}

export enum DeviceCategory {
	GENERIC = 'generic',
}

export enum ChannelCategory {
	GENERIC = 'generic',
}

export enum PropertyCategory {
	GENERIC = 'generic',
}

export interface ConnectorDocument {
	id: string;
	type: string;
	source: string;
	category: ConnectorCategory;
	identifier: string;
	name: string;
	comment: string | null;
	enabled: boolean;
	properties: string[];
	controls: string[];
	devices: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ConnectorPropertyDocument {
	id: string;
	type: 'dynamic' | 'variable';
	source: string;
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;
	value_transformer: string | null;
	default?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	actual_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	is_valid?: boolean;
	connector: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ConnectorControlDocument {
	id: string;
	type: string;
	source: string;
	name: string;
	connector: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface DeviceDocument {
	id: string;
	type: string;
	source: string;
	category: DeviceCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	connector: string;
	parents: string[];
	children: string[];
	properties: string[];
	controls: string[];
	channels: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface DevicePropertyDocument {
	id: string;
	type: 'dynamic' | 'variable' | 'mapped';
	source: string;
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;
	value_transformer: string | null;
	default?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	actual_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	is_valid?: boolean;
	device: string;
	parent?: string;
	children: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface DeviceControlDocument {
	id: string;
	type: string;
	source: string;
	name: string;
	device: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ChannelDocument {
	id: string;
	type: string;
	source: string;
	category: ChannelCategory;
	identifier: string;
	name: string | null;
	comment: string | null;
	device: string;
	properties: string[];
	controls: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ChannelPropertyDocument {
	id: string;
	type: 'dynamic' | 'variable' | 'mapped';
	source: string;
	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	data_type: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;
	value_transformer: string | null;
	default?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	actual_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expected_value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	is_valid?: boolean;
	channel: string;
	parent?: string;
	children: string[];
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}

export interface ChannelControlDocument {
	id: string;
	type: string;
	source: string;
	name: string;
	channel: string;
	owner: string | null;
	createdAt: Date | null;
	updatedAt: Date | null;
}
