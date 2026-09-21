import { TJsonaModel } from 'jsona/lib/JsonaTypes';

import { ButtonPayload, CoverPayload, DataType, SwitchPayload } from '@fastybird/miniserver-core';

import { IEntityMeta, PropertyCategory } from '../../types';

export interface IPropertyMeta extends IEntityMeta {
	parent: 'connector' | 'device' | 'channel';
	entity: 'property';
}

// COMMANDS
// ========

export enum PropertyCommandState {
	SENDING = 'sending',
	COMPLETED = 'completed',
}

export enum PropertyCommandResult {
	OK = 'ok',
	ERR = 'err',
}

// STORE MODELS
// ============

export interface IProperty {
	id: string;
	type: IPropertyMeta;

	draft: boolean;

	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;
	valueTransformer: string | null;
	default: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	// Static property
	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	/* Dynamic property start */
	actualValue: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expectedValue: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending: boolean | Date;
	isValid: boolean;

	command: PropertyCommandState | null;
	lastResult: PropertyCommandResult | null;
	backupValue: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	/* Dynamic property end */

	// Relations
	relationshipNames: string[];

	title: string;
}

// STORE DATA FACTORIES
// ====================

export interface IPropertyRecordFactoryPayload {
	id?: string;
	type: IPropertyMeta;

	draft?: boolean;

	category: PropertyCategory;
	identifier: string;
	name?: string | null;
	settable?: boolean;
	queryable?: boolean;
	dataType: DataType;
	unit?: string | null;
	format?: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid?: string | number | boolean | null;
	scale?: number | null;
	step?: number | null;

	// Static property
	value?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	/* Dynamic property start */
	actualValue?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expectedValue?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending?: boolean | Date;
	isValid?: boolean;

	command?: PropertyCommandState | null;
	lastResult?: PropertyCommandResult | null;
	backupValue?: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	/* Dynamic property end */

	// Relations
	relationshipNames?: string[];
}

// STORE ACTIONS
// =============

export interface IPropertiesAddActionPayload {
	id?: IProperty['id'];
	type: IPropertyMeta;

	draft?: IProperty['draft'];

	data: {
		identifier: IProperty['identifier'];
		name?: IProperty['name'];
		settable?: IProperty['settable'];
		queryable?: IProperty['queryable'];
		dataType: IProperty['dataType'];
		unit?: IProperty['unit'];
		format?: IProperty['format'];
		invalid?: IProperty['invalid'];
		scale?: IProperty['scale'];
		step?: IProperty['step'];

		// Static property
		value?: IProperty['value'];
	};
}

export interface IPropertiesEditActionPayload {
	id: IProperty['id'];

	data: {
		identifier?: IProperty['identifier'];
		name?: IProperty['name'];
		settable?: IProperty['settable'];
		queryable?: IProperty['queryable'];
		dataType?: IProperty['dataType'];
		unit?: IProperty['unit'];
		format?: IProperty['format'];
		invalid?: IProperty['invalid'];
		scale?: IProperty['scale'];
		step?: IProperty['step'];

		// Static property
		value?: IProperty['value'];

		/* Dynamic property start */
		actualValue?: IProperty['actualValue'];
		expectedValue?: IProperty['expectedValue'];
		pending?: IProperty['pending'];
		isValid?: IProperty['isValid'];

		command?: IProperty['command'];
		lastResult?: IProperty['lastResult'];
		backupValue?: IProperty['backupValue'];
		/* Dynamic property end */
	};
}

export interface IPropertiesSetStateActionPayload {
	id: IProperty['id'];

	data: {
		actualValue?: IProperty['actualValue'];
		expectedValue?: IProperty['expectedValue'];
		pending?: IProperty['pending'];
		isValid?: IProperty['isValid'];

		command?: IProperty['command'];
		lastResult?: IProperty['lastResult'];
		backupValue?: IProperty['backupValue'];
	};
}

// API RESPONSE MODELS
// ===================

export interface IPropertyResponseModel extends TJsonaModel {
	id: string;
	type: IPropertyMeta;

	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;

	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	actualValue: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	expectedValue: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;
	pending: boolean | Date;
	isValid: boolean;

	// Relations
	relationshipNames: string[];
}

// DATABASE
// ========

export interface IPropertyDatabaseRecord {
	id: string;
	type: IPropertyMeta;

	category: PropertyCategory;
	identifier: string;
	name: string | null;
	settable: boolean;
	queryable: boolean;
	dataType: DataType;
	unit: string | null;
	format: string[] | (string | null)[][] | (number | null)[] | string | null;
	invalid: string | number | boolean | null;
	scale: number | null;
	step: number | null;
	valueTransformer: string | null;
	default: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	// Static property
	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null;

	// Relations
	relationshipNames: string[];
}
