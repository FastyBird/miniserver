// The JSON:API types the Accounts, Devices and Ui modules share with jsona. jsona 1.14 declares
// them in `lib/JsonaTypes.d.ts` but its `exports` map only exposes the package root, so under
// `moduleResolution: "bundler"` the `jsona/lib/JsonaTypes` subpath cannot be imported, and the
// root re-exports none of them. They are owned here instead, copied from that declaration file
// without changes of meaning. They are structurally identical to jsona's, which is what lets a
// `Jsona` instance accept values typed with them. Re-compare against `lib/JsonaTypes.d.ts` when
// jsona is upgraded.

type AtLeastOneProperty<T, U = { [K in keyof T]: Pick<T, K> }> = Partial<T> & U[keyof U];

export type TAnyKeyValueObject = {
	[key: string]: any;
};

export type TJsonaModel = {
	[propertyName: string]: any;
};

type LinkKey = 'self' | 'related' | 'first' | 'prev' | 'next' | 'last';

type LinkObjectMember =
	| string
	| {
			href?: string;
			meta?: TAnyKeyValueObject;
	  }
	| null;

type TJsonApiLinks = {
	[key in LinkKey]?: LinkObjectMember;
};

type TJsonApiRelationshipData = {
	type: string;
	id: string | number;
	meta?: TAnyKeyValueObject;
	[propertyName: string]: any;
};

type FullTJsonApiRelation = {
	data: TJsonApiRelationshipData | Array<TJsonApiRelationshipData> | null;
	links: TJsonApiLinks;
	meta: TAnyKeyValueObject;
};

export type TJsonApiRelation = AtLeastOneProperty<FullTJsonApiRelation>;

export type TJsonApiRelationships = {
	[relationName: string]: TJsonApiRelation;
};

export type TJsonApiData = {
	type: string;
	id?: string | number;
	attributes?: TAnyKeyValueObject;
	meta?: TAnyKeyValueObject;
	links?: TJsonApiLinks;
	relationships?: TJsonApiRelationships;
};

export type TJsonApiBody = {
	data?: TJsonApiData | TJsonApiData[];
	included?: Array<TJsonApiData>;
};

export type TJsonaRelationshipBuild = () => TJsonaModel | Array<TJsonaModel>;

export type TJsonaRelationships = {
	[relationName: string]: TJsonaRelationshipBuild | TJsonaModel | Array<TJsonaModel>;
};

export interface IModelPropertiesMapper {
	getId(model: TJsonaModel): string | number;
	getType(model: TJsonaModel): string;
	getAttributes(model: TJsonaModel): TAnyKeyValueObject;
	getRelationships(model: TJsonaModel): TJsonaRelationships;
}

export interface IJsonPropertiesMapper {
	createModel(type: string): TJsonaModel;
	setId(model: TJsonaModel, id: string | number): void;
	setAttributes(model: TJsonaModel, attributes: TAnyKeyValueObject): void;
	setMeta(model: TJsonaModel, meta: TAnyKeyValueObject): void;
	setLinks(model: TJsonaModel, links: TAnyKeyValueObject): void;
	setResourceIdObjMeta(model: TJsonaModel, meta: unknown): void;
	setRelationships(model: TJsonaModel, relationships: TJsonaRelationships): void;
	setRelationshipLinks(parentModel: TJsonaModel, relationName: string, links: TJsonApiLinks): void;
	setRelationshipMeta(parentModel: TJsonaModel, relationName: string, meta: TAnyKeyValueObject): void;
}
