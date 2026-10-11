import { JsonPropertiesMapper } from 'jsona';
import { IJsonPropertiesMapper, TAnyKeyValueObject, TJsonaModel, TJsonaRelationships } from 'jsona/lib/JsonaTypes';
import get from 'lodash.get';

import { DataType } from '@fastybird/miniserver-core';

import { useNormalizeValue } from '../composables';
import { PropertyType } from '../types';

import { RELATIONSHIP_NAMES_PROP } from './constants';
import {
	ANY_CONTROL_DOCUMENT_REG_EXP,
	ANY_PROPERTY_DOCUMENT_REG_EXP,
	CHANNEL_DOCUMENT_REG_EXP,
	CONNECTOR_DOCUMENT_REG_EXP,
	DEVICE_DOCUMENT_REG_EXP,
} from './utilities';

const CASE_REG_EXP = '_([a-z0-9])';

class JsonApiJsonPropertiesMapper extends JsonPropertiesMapper implements IJsonPropertiesMapper {
	connectorTypeRegex: RegExp;
	deviceTypeRegex: RegExp;
	channelTypeRegex: RegExp;
	controlTypeRegex: RegExp;
	propertyTypeRegex: RegExp;

	constructor() {
		super();

		this.connectorTypeRegex = new RegExp(CONNECTOR_DOCUMENT_REG_EXP);
		this.deviceTypeRegex = new RegExp(DEVICE_DOCUMENT_REG_EXP);
		this.channelTypeRegex = new RegExp(CHANNEL_DOCUMENT_REG_EXP);
		this.controlTypeRegex = new RegExp(ANY_CONTROL_DOCUMENT_REG_EXP);
		this.propertyTypeRegex = new RegExp(ANY_PROPERTY_DOCUMENT_REG_EXP);
	}

	createModel(type: string): TJsonaModel {
		if (this.connectorTypeRegex.test(type)) {
			const parsedTypes = this.connectorTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', entity: 'connector' }, ...parsedTypes?.groups } };
		}

		if (this.deviceTypeRegex.test(type)) {
			const parsedTypes = this.deviceTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', entity: 'device' }, ...parsedTypes?.groups } };
		}

		if (this.channelTypeRegex.test(type)) {
			const parsedTypes = this.channelTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', entity: 'channel' }, ...parsedTypes?.groups } };
		}

		if (this.controlTypeRegex.test(type)) {
			const parsedTypes = this.controlTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', parent: 'N/A', entity: 'control' }, ...parsedTypes?.groups } };
		}

		if (this.propertyTypeRegex.test(type)) {
			const parsedTypes = this.propertyTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', parent: 'N/A', entity: 'property' }, ...parsedTypes?.groups } };
		}

		return { type };
	}

	setAttributes(model: TJsonaModel, attributes: TAnyKeyValueObject): void {
		Object.assign(model, JsonApiJsonPropertiesMapper.camelizeAttributes(attributes));

		if (get(model, 'type.entity') === 'property') {
			Object.assign(model, JsonApiJsonPropertiesMapper.cleanPropertyAttributes(model));
		}
	}

	setRelationships(model: TJsonaModel, relationships: TJsonaRelationships): void {
		// Call super.setRelationships first, just for not to copy&paste setRelationships logic
		super.setRelationships(model, relationships);

		const caseRegex = new RegExp(CASE_REG_EXP, 'g');

		model[RELATIONSHIP_NAMES_PROP].forEach((relationName: string, index: number): void => {
			const camelName = relationName.replaceAll(caseRegex, (g) => g[1].toUpperCase());

			if (camelName !== relationName) {
				Object.assign(model, { [camelName]: model[relationName] });

				delete model[relationName];

				model[RELATIONSHIP_NAMES_PROP][index] = camelName;
			}
		});

		Object.assign(model, {
			[RELATIONSHIP_NAMES_PROP]: (model[RELATIONSHIP_NAMES_PROP] as string[]).filter((value, i, self) => self.indexOf(value) === i),
		});
	}

	/**
	 * Convert object keys to camel cased keys
	 *
	 * @param {TAnyKeyValueObject} attributes
	 *
	 * @private
	 */
	private static camelizeAttributes(attributes: TAnyKeyValueObject): TAnyKeyValueObject {
		const caseRegex = new RegExp(CASE_REG_EXP, 'g');

		const data: TAnyKeyValueObject = {};

		Object.keys(attributes).forEach((attrName): void => {
			let camelName = attrName.replace(caseRegex, (g) => g[1].toUpperCase());
			camelName = camelName.replaceAll(caseRegex, (g) => g[1].toUpperCase());

			if (typeof attributes[attrName] === 'object' && attributes[attrName] !== null && !Array.isArray(attributes[attrName])) {
				Object.assign(data, { [camelName]: JsonApiJsonPropertiesMapper.camelizeAttributes(attributes[attrName]) });
			} else {
				Object.assign(data, { [camelName]: attributes[attrName] });
			}
		});

		return data;
	}

	private static cleanPropertyAttributes(attributes: TAnyKeyValueObject): TAnyKeyValueObject {
		if ('invalid' in attributes && 'dataType' in attributes) {
			attributes.invalid = JsonApiJsonPropertiesMapper.cleanInvalidValue(attributes.dataType, attributes.invalid);
		}

		if (get(attributes, 'type.type') === PropertyType.DYNAMIC) {
			attributes.actualValue = useNormalizeValue(attributes.dataType, attributes.actualValue, attributes.format, attributes.scale);
			attributes.expectedValue = useNormalizeValue(attributes.dataType, attributes.expectedValue, attributes.format, attributes.scale);
		}

		if (get(attributes, 'type.type') === PropertyType.VARIABLE) {
			attributes.value = useNormalizeValue(attributes.dataType, attributes.value, attributes.format, attributes.scale);
		}

		return attributes;
	}

	private static cleanInvalidValue(dataType: string | null, rawInvalid?: string | number | null): string | number | null {
		if (rawInvalid === null || rawInvalid === undefined || dataType === null) {
			return null;
		}

		switch (dataType) {
			case DataType.CHAR:
			case DataType.UCHAR:
			case DataType.SHORT:
			case DataType.USHORT:
			case DataType.INT:
			case DataType.UINT: {
				if (!isNaN(Number(rawInvalid))) {
					return parseInt(String(rawInvalid), 10);
				}

				return null;
			}

			case DataType.FLOAT: {
				if (!isNaN(Number(rawInvalid))) {
					return parseFloat(String(rawInvalid));
				}

				return null;
			}

			default: {
				return String(rawInvalid);
			}
		}
	}
}

export default JsonApiJsonPropertiesMapper;
