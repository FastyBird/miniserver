import { parse } from 'date-fns';

import { ButtonPayload, DataType, SwitchPayload } from '@fastybird/miniserver-core';

export function useNormalizeValue(
	dataType: DataType,
	value: string | number | boolean | Date | null,
	format?: string[] | (string | null)[][] | (number | null)[] | string | null,
	scale?: number | null
): string | number | boolean | Date | null {
	if (value === null) {
		return null;
	}

	switch (dataType) {
		case DataType.BOOLEAN:
			return ['true', 't', 'yes', 'y', '1', 'on'].includes(String(value).toLowerCase());

		case DataType.FLOAT: {
			const floatValue = parseFloat(String(value));

			if (Array.isArray(format) && format.length === 2) {
				if (format[0] !== null && parseFloat(`${format[0]}`) > floatValue) {
					return null;
				}

				if (format[1] !== null && parseFloat(`${format[1]}`) < floatValue) {
					return null;
				}
			}

			return floatValue;
		}

		case DataType.CHAR:
		case DataType.UCHAR:
		case DataType.SHORT:
		case DataType.USHORT:
		case DataType.INT:
		case DataType.UINT: {
			if (scale) {
				const floatValue = parseFloat(String(value));

				if (Array.isArray(format) && format.length === 2) {
					if (format[0] !== null) {
						let minValue = parseFloat(String(format[0]));

						for (let i = 0; i < scale; i++) {
							minValue = minValue / 10;
						}

						if (minValue > floatValue) {
							return null;
						}
					}

					if (format[1] !== null) {
						let minValue = parseFloat(String(format[1]));

						for (let i = 0; i < scale; i++) {
							minValue = minValue / 10;
						}

						if (minValue < floatValue) {
							return null;
						}
					}
				}

				return floatValue;
			} else {
				const intValue = parseInt(String(value), 10);

				if (Array.isArray(format) && format.length === 2) {
					if (format[0] !== null && parseFloat(`${format[0]}`) > intValue) {
						return null;
					}

					if (format[1] !== null && parseFloat(`${format[1]}`) < intValue) {
						return null;
					}
				}

				return intValue;
			}
		}

		case DataType.STRING:
			return value;

		case DataType.ENUM:
			if (Array.isArray(format)) {
				const filtered = (format as (string | (string | null)[])[]).filter((item): boolean => {
					if (Array.isArray(item)) {
						if (item.length !== 3) {
							return false;
						}

						return String(value).toLowerCase() === item[0] || String(value).toLowerCase() === item[1] || String(value).toLowerCase() === item[2];
					}

					return String(value).toLowerCase() === item;
				});

				return filtered.length === 1 ? (Array.isArray(filtered[0]) ? filtered[0][0] : filtered[0]) : null;
			}

			return null;

		case DataType.DATE:
			return parse(String(value), 'yyyy-MM-DD', new Date());

		case DataType.TIME:
			return parse(String(value), 'HH:mm:ssxxx', new Date());

		case DataType.DATETIME:
			return parse(String(value), "yyyy-MM-DD'T'HH:mm:ssxxx", new Date());

		case DataType.BUTTON:
			if (Object.values<string>(ButtonPayload).includes(String(value).toLowerCase())) {
				return String(value).toLowerCase();
			}

			return null;

		case DataType.SWITCH:
			if (Object.values<string>(SwitchPayload).includes(String(value).toLowerCase())) {
				return String(value).toLowerCase();
			}

			return null;
	}

	return value;
}
