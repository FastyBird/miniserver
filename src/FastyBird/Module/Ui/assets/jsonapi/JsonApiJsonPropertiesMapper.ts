import { IJsonPropertiesMapper, TAnyKeyValueObject, TJsonaModel, TJsonaRelationships } from 'jsona/lib/JsonaTypes';
import { JsonPropertiesMapper, RELATIONSHIP_NAMES_PROP } from 'jsona/lib/simplePropertyMappers';

import {
	DASHBOARD_DOCUMENT_REG_EXP,
	GROUP_DOCUMENT_REG_EXP,
	TAB_DOCUMENT_REG_EXP,
	WIDGET_DATA_SOURCE_DOCUMENT_REG_EXP,
	WIDGET_DISPLAY_DOCUMENT_REG_EXP,
	WIDGET_DOCUMENT_REG_EXP,
} from './utilities';

const CASE_REG_EXP = '_([a-z0-9])';

class JsonApiJsonPropertiesMapper extends JsonPropertiesMapper implements IJsonPropertiesMapper {
	dashboardTypeRegex: RegExp;
	tabTypeRegex: RegExp;
	groupTypeRegex: RegExp;
	widgetTypeRegex: RegExp;
	widgetDisplayTypeRegex: RegExp;
	widgetDataSourceTypeRegex: RegExp;

	constructor() {
		super();

		this.dashboardTypeRegex = new RegExp(DASHBOARD_DOCUMENT_REG_EXP);
		this.tabTypeRegex = new RegExp(TAB_DOCUMENT_REG_EXP);
		this.groupTypeRegex = new RegExp(GROUP_DOCUMENT_REG_EXP);
		this.widgetTypeRegex = new RegExp(WIDGET_DOCUMENT_REG_EXP);
		this.widgetDisplayTypeRegex = new RegExp(WIDGET_DISPLAY_DOCUMENT_REG_EXP);
		this.widgetDataSourceTypeRegex = new RegExp(WIDGET_DATA_SOURCE_DOCUMENT_REG_EXP);
	}

	createModel(type: string): TJsonaModel {
		if (this.dashboardTypeRegex.test(type)) {
			const parsedTypes = this.dashboardTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'dashboard' }, ...parsedTypes?.groups } };
		}

		if (this.tabTypeRegex.test(type)) {
			const parsedTypes = this.tabTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'tab' }, ...parsedTypes?.groups } };
		}

		if (this.groupTypeRegex.test(type)) {
			const parsedTypes = this.groupTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'group' }, ...parsedTypes?.groups } };
		}

		if (this.widgetTypeRegex.test(type)) {
			const parsedTypes = this.widgetTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', entity: 'widget' }, ...parsedTypes?.groups } };
		}

		if (this.widgetDisplayTypeRegex.test(type)) {
			const parsedTypes = this.widgetDisplayTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', entity: 'display' }, ...parsedTypes?.groups } };
		}

		if (this.widgetDataSourceTypeRegex.test(type)) {
			const parsedTypes = this.widgetDataSourceTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', type: 'N/A', entity: 'data-source' }, ...parsedTypes?.groups } };
		}

		return { type };
	}

	setAttributes(model: TJsonaModel, attributes: TAnyKeyValueObject): void {
		Object.assign(model, JsonApiJsonPropertiesMapper.camelizeAttributes(attributes));
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
}

export default JsonApiJsonPropertiesMapper;
