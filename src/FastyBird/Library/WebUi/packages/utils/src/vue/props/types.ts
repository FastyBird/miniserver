import type { ExtractPropTypes, PropType } from 'vue';

import type { fbPropKey } from './runtime';
import type { IfNever, UnknownToNever, WritableArray } from './util';

type Value<T> = T[keyof T];

/**
 * Extract the type of a single prop
 *
 * @example
 * ExtractPropType<{ type: StringConstructor }> => string | undefined
 * ExtractPropType<{ type: StringConstructor, required: true }> => string
 * ExtractPropType<{ type: BooleanConstructor }> => boolean
 */
export type ExtractPropType<T extends object> = Value<
	ExtractPropTypes<{
		key: T;
	}>
>;

/**
 * Extracts types via `ExtractPropTypes`, accepting `PropType<T>`, `XXXConstructor`, `never`...
 *
 * @example
 * ResolvePropType<BooleanConstructor> => boolean
 * ResolvePropType<PropType<T>> => T
 **/
export type ResolvePropType<T> = IfNever<
	T,
	never,
	ExtractPropType<{
		type: WritableArray<T>;
		required: true;
	}>
>;

/**
 * Merge Type, Value, Validator types
 *
 * @example
 * FbPropMergeType<StringConstructor, '1', 1> =>  1 | "1" // ignores StringConstructor
 * FbPropMergeType<StringConstructor, never, number> =>  string | number
 */
export type FbPropMergeType<Type, Value, Validator> =
	IfNever<UnknownToNever<Value>, ResolvePropType<Type>, never> | UnknownToNever<Value> | UnknownToNever<Validator>;

/**
 * Handling default values for input (constraints)
 */
export type FbPropInputDefault<Required extends boolean, Default> = Required extends true
	? never
	: Default extends Record<string, any> | Array<any>
		? () => Default
		: (() => Default) | Default;

/**
 * Native prop types, e.g: `BooleanConstructor`, `StringConstructor`, `null`, `undefined`, etc.
 */
export type NativePropType = ((...args: any) => any) | { new (...args: any): any } | undefined | null;
export type IfNativePropType<T, Y, N> = [T] extends [NativePropType] ? Y : N;

/**
 * input prop `buildProp` or `buildProps` (constraints)
 *
 * @example
 * FbPropInput<StringConstructor, 'a', never, never, true>
 * ⬇️
 * {
    type?: StringConstructor | undefined;
    required?: true | undefined;
    values?: readonly "a"[] | undefined;
    validator?: ((val: any) => boolean) | ((val: any) => val is never) | undefined;
    default?: undefined;
  }
 */
export type FbPropInput<Type, Value, Validator, Default extends FbPropMergeType<Type, Value, Validator>, Required extends boolean> = {
	type?: Type;
	required?: Required;
	values?: readonly Value[];
	validator?: ((val: any) => val is Validator) | ((val: any) => boolean);
	default?: FbPropInputDefault<Required, Default>;
};

/**
 * output prop `buildProp` or `buildProps`
 *
 * @example
 * FbProp<'a', 'b', true>
 * ⬇️
 * {
    readonly type: PropType<"a">;
    readonly required: true;
    readonly validator: ((val: any) => boolean) | undefined;
    readonly default: "b";
    __fbPropKey: true;
  }
 */
export type FbProp<Type, Default, Required> = {
	readonly type: PropType<Type>;
	readonly required: [Required] extends [true] ? true : false;
	readonly validator: ((val: any) => boolean) | undefined;
	[fbPropKey]: true;
} & IfNever<Default, any, { readonly default: Default }>;

/**
 * Determine if it is `FbProp`
 */
export type IfFbProp<T, Y, N> = T extends { [fbPropKey]: true } ? Y : N;

/**
 * Converting input to output
 */
export type FbPropConvert<Input> =
	Input extends FbPropInput<infer Type, infer Value, infer Validator, any, infer Required>
		? FbPropFinalized<Type, Value, Validator, Input['default'], Required>
		: never;

/**
 * Finalized conversion output
 */
export type FbPropFinalized<Type, Value, Validator, Default, Required> = FbProp<
	FbPropMergeType<Type, Value, Validator>,
	UnknownToNever<Default>,
	Required
>;

export {};
