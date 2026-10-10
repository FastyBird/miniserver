import { ModelPropertiesMapper } from 'jsona';
import { IModelPropertiesMapper, TAnyKeyValueObject, TJsonaModel, TJsonaRelationships } from 'jsona/lib/JsonaTypes';
import get from 'lodash.get';

import { RELATIONSHIP_NAMES_PROP } from './constants';

class JsonApiModelPropertiesMapper extends ModelPropertiesMapper implements IModelPropertiesMapper {
	exceptedAttributes: string[];

	constructor(exceptedAttributes: string[] = ['id', 'type', 'draft', RELATIONSHIP_NAMES_PROP]) {
		super();

		this.exceptedAttributes = exceptedAttributes;
	}

	getType(model: TJsonaModel): string {
		const typeParts: string[] = [];

		typeParts.push(get(model, 'type.source'));
		typeParts.push(get(model, 'type.entity'));

		if (get(model, 'type.type', null) !== null) {
			typeParts.push(get(model, 'type.type'));
		}

		return typeParts.join('/');
	}

	getAttributes(model: TJsonaModel): { [index: string]: any } {
		const camelCasedAttributes = super.getAttributes(model);

		if (Array.isArray(model[RELATIONSHIP_NAMES_PROP])) {
			this.exceptedAttributes.push(...model[RELATIONSHIP_NAMES_PROP]);
		}

		return Object.assign({}, this.snakelizeAttributes(camelCasedAttributes, this.exceptedAttributes));
	}

	getRelationships(model: TJsonaModel): TJsonaRelationships {
		const camelCasedRelationships: TJsonaRelationships = super.getRelationships(model);

		if (typeof camelCasedRelationships === 'undefined') {
			return camelCasedRelationships;
		}

		const snakeRelationships: TJsonaRelationships = {};

		Object.keys(camelCasedRelationships).forEach((relationName): void => {
			const snakeName = relationName.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

			if (camelCasedRelationships[relationName] !== null) {
				snakeRelationships[snakeName] = camelCasedRelationships[relationName];
			}
		});

		return snakeRelationships;
	}

	private snakelizeAttributes(attributes: TAnyKeyValueObject, excepted: string[]): TAnyKeyValueObject {
		const data: TAnyKeyValueObject = {};

		Object.keys(attributes)
			.filter((attrName): boolean => !excepted.includes(attrName))
			.forEach((attrName): void => {
				const snakeName = attrName.replace(/[A-Z]/g, (letter) => `_${letter.toLowerCase()}`);

				if (typeof attributes[attrName] === 'object' && attributes[attrName] !== null && !Array.isArray(attributes[attrName])) {
					Object.assign(data, { [snakeName]: this.snakelizeAttributes(attributes[attrName], excepted) });
				} else {
					Object.assign(data, { [snakeName]: attributes[attrName] });
				}
			});

		return data;
	}
}

export default JsonApiModelPropertiesMapper;
