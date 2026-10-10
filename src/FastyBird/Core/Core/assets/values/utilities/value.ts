import { ButtonPayload, CoverPayload, SwitchPayload } from '../../metadata';

export const flattenValue = (
	value: string | number | boolean | ButtonPayload | CoverPayload | SwitchPayload | Date | null
): string | number | boolean | null => {
	if (value === null) {
		return null;
	}

	if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
		return value;
	}

	if (value instanceof Date) {
		return value.toISOString();
	}

	if (
		Object.values(SwitchPayload).includes(value as SwitchPayload) ||
		Object.values(CoverPayload).includes(value as CoverPayload) ||
		Object.values(ButtonPayload).includes(value as ButtonPayload)
	) {
		return value as string;
	}

	return null;
};
