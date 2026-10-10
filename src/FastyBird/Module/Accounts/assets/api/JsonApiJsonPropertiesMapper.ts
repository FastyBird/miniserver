import { JsonPropertiesMapper } from 'jsona';
import { IJsonPropertiesMapper, TAnyKeyValueObject, TJsonaModel, TJsonaRelationships } from 'jsona/lib/JsonaTypes';

import { RELATIONSHIP_NAMES_PROP } from './constants';
import {
	ACCOUNT_DOCUMENT_REG_EXP,
	EMAIL_DOCUMENT_REG_EXP,
	IDENTITY_DOCUMENT_REG_EXP,
	ROLE_DOCUMENT_REG_EXP,
	SESSION_DOCUMENT_REG_EXP,
} from './utilities';

const CASE_REG_EXP = '_([a-z0-9])';

class JsonApiJsonPropertiesMapper extends JsonPropertiesMapper implements IJsonPropertiesMapper {
	accountTypeRegex: RegExp;
	emailTypeRegex: RegExp;
	identityTypeRegex: RegExp;
	roleTypeRegex: RegExp;
	sessionTypeRegex: RegExp;

	constructor() {
		super();

		this.accountTypeRegex = new RegExp(ACCOUNT_DOCUMENT_REG_EXP);
		this.emailTypeRegex = new RegExp(EMAIL_DOCUMENT_REG_EXP);
		this.identityTypeRegex = new RegExp(IDENTITY_DOCUMENT_REG_EXP);
		this.roleTypeRegex = new RegExp(ROLE_DOCUMENT_REG_EXP);
		this.sessionTypeRegex = new RegExp(SESSION_DOCUMENT_REG_EXP);
	}

	createModel(type: string): TJsonaModel {
		if (this.accountTypeRegex.test(type)) {
			const parsedTypes = this.accountTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'account' }, ...parsedTypes?.groups } };
		}

		if (this.emailTypeRegex.test(type)) {
			const parsedTypes = this.emailTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'email' }, ...parsedTypes?.groups } };
		}

		if (this.identityTypeRegex.test(type)) {
			const parsedTypes = this.identityTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'identity' }, ...parsedTypes?.groups } };
		}

		if (this.roleTypeRegex.test(type)) {
			const parsedTypes = this.roleTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'role' }, ...parsedTypes?.groups } };
		}

		if (this.sessionTypeRegex.test(type)) {
			const parsedTypes = this.sessionTypeRegex.exec(type);

			return { type: { ...{ source: 'N/A', entity: 'session' }, ...parsedTypes?.groups } };
		}

		return { type };
	}

	setAttributes(model: TJsonaModel, attributes: TAnyKeyValueObject): void {
		Object.assign(model, this.camelizeAttributes(attributes));
	}

	setRelationships(model: TJsonaModel, relationships: TJsonaRelationships): void {
		// Call super.setRelationships first, just for not to copy&paste setRelationships logic
		super.setRelationships(model, relationships);

		const caseRegex = new RegExp(CASE_REG_EXP);

		model[RELATIONSHIP_NAMES_PROP].forEach((relationName: string, index: number): void => {
			const camelName = relationName.replace(caseRegex, (g) => g[1].toUpperCase());

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

	private camelizeAttributes(attributes: TAnyKeyValueObject): TAnyKeyValueObject {
		const caseRegex = new RegExp(CASE_REG_EXP);

		const data: TAnyKeyValueObject = {};

		Object.keys(attributes).forEach((attrName): void => {
			const camelName = attrName.replace(caseRegex, (g) => g[1].toUpperCase());

			if (typeof attributes[attrName] === 'object' && attributes[attrName] !== null && !Array.isArray(attributes[attrName])) {
				Object.assign(data, { [camelName]: this.camelizeAttributes(attributes[attrName]) });
			} else {
				Object.assign(data, { [camelName]: attributes[attrName] });
			}
		});

		return data;
	}
}

export default JsonApiJsonPropertiesMapper;
