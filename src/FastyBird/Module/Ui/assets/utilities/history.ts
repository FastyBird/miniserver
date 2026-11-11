// Everything here is plain TypeScript with no Vue, axios or store import: the parsing and the
// range rules are the part of the chart that can be wrong without anything crashing, so they
// stay where they can be run on their own.

export const HISTORY_DEFAULT_PERIOD = 86400;
export const HISTORY_MIN_PERIOD = 60;
export const HISTORY_MAX_PERIOD = 31622400;

export const HISTORY_DEFAULT_POINTS = 500;
export const HISTORY_MAX_POINTS = 2000;

/**
 * The periods the chart offers: 1 h, 24 h, 7 d, 30 d and 1 y
 */
export const HISTORY_PERIODS: number[] = [3600, 86400, 604800, 2592000, 31536000];

export type HistoryTier = 'samples' | 'five_minutes' | 'hours';

const HISTORY_TIERS: string[] = ['samples', 'five_minutes', 'hours'];

/**
 * Value columns of the history, one entry per point. A null is a gap: no change was recorded in it.
 */
export interface IHistoryColumns {
	time: number[];
	min: (number | null)[];
	max: (number | null)[];
	avg: (number | null)[];
	last: (number | null)[];
}

export interface IHistorySeries {
	id: string;
	from: Date;
	to: Date;
	resolution: number;
	tier: HistoryTier;
	unit: string | null;
	dataType: string;
	columns: IHistoryColumns;
}

export type HistoryErrorKind = 'invalid' | 'notFound' | 'unavailable' | 'malformed';

export class HistoryParseError extends Error {
	constructor(message: string) {
		super(message);

		this.name = 'HistoryParseError';
	}
}

/**
 * A usable period in seconds: the configured one when it is within the limits the endpoint accepts, else the default
 *
 * @param {number | null | undefined} period
 */
export const normalizePeriod = (period: number | null | undefined): number => {
	if (typeof period !== 'number' || !Number.isInteger(period) || period < HISTORY_MIN_PERIOD || period > HISTORY_MAX_PERIOD) {
		return HISTORY_DEFAULT_PERIOD;
	}

	return period;
};

/**
 * Query of the history endpoint
 *
 * @param {number | null | undefined} period
 * @param {number} points
 */
export const historyQuery = (period: number | null | undefined, points: number = HISTORY_DEFAULT_POINTS): Record<string, number> => {
	const count = Number.isInteger(points) ? Math.min(Math.max(points, 1), HISTORY_MAX_POINTS) : HISTORY_DEFAULT_POINTS;

	return {
		'filter[period]': normalizePeriod(period),
		'filter[points]': count,
	};
};

/**
 * The periods of the selector: the presets, and the configured period too when it is not one of them
 *
 * @param {number | null | undefined} current
 */
export const periodOptions = (current: number | null | undefined): number[] => {
	return [...new Set([...HISTORY_PERIODS, normalizePeriod(current)])].sort((a, b) => a - b);
};

export interface IPeriodLabel {
	unit: 'minutes' | 'hours' | 'days' | 'years';
	count: number;
}

/**
 * The largest whole unit a period is made of, so 86400 reads "1 day" next to 604800 reading "7 days"
 *
 * @param {number} period
 */
export const periodLabel = (period: number): IPeriodLabel => {
	if (period % 31536000 === 0) {
		return { unit: 'years', count: period / 31536000 };
	}

	if (period % 86400 === 0) {
		return { unit: 'days', count: period / 86400 };
	}

	if (period % 3600 === 0) {
		return { unit: 'hours', count: period / 3600 };
	}

	return { unit: 'minutes', count: Math.max(Math.ceil(period / 60), 1) };
};

const isRecord = (value: unknown): value is Record<string, unknown> => typeof value === 'object' && value !== null && !Array.isArray(value);

const numberColumn = (attributes: Record<string, unknown>, name: string, nullable: boolean): (number | null)[] => {
	const column = attributes[name];

	if (!Array.isArray(column)) {
		throw new HistoryParseError(`History column "${name}" is not a list`);
	}

	return column.map((value: unknown): number | null => {
		if (value === null && nullable) {
			return null;
		}

		if (typeof value !== 'number' || !Number.isFinite(value)) {
			throw new HistoryParseError(`History column "${name}" holds a value that is not a number`);
		}

		return value;
	});
};

const dateAttribute = (attributes: Record<string, unknown>, name: string): Date => {
	const value = attributes[name];
	const date = typeof value === 'string' ? new Date(value) : null;

	if (date === null || Number.isNaN(date.getTime())) {
		throw new HistoryParseError(`History attribute "${name}" is not a date`);
	}

	return date;
};

/**
 * Series from the JSON:API body of the history endpoint
 *
 * @param {unknown} body
 */
export const parseHistory = (body: unknown): IHistorySeries => {
	const data = isRecord(body) ? body.data : null;
	const attributes = isRecord(data) ? data.attributes : null;

	if (!isRecord(data) || !isRecord(attributes) || typeof data.id !== 'string') {
		throw new HistoryParseError('History response is not a JSON:API resource');
	}

	const tier = attributes.tier;

	if (typeof tier !== 'string' || !HISTORY_TIERS.includes(tier)) {
		throw new HistoryParseError('History attribute "tier" is not a known tier');
	}

	const resolution = attributes.resolution;

	if (typeof resolution !== 'number' || !Number.isFinite(resolution) || resolution <= 0) {
		throw new HistoryParseError('History attribute "resolution" is not a positive number');
	}

	const time = numberColumn(attributes, 'time', false) as number[];
	const min = numberColumn(attributes, 'min', true);
	const max = numberColumn(attributes, 'max', true);
	const avg = numberColumn(attributes, 'avg', true);
	const last = numberColumn(attributes, 'last', true);

	if ([min, max, avg, last].some((column) => column.length !== time.length)) {
		throw new HistoryParseError('History columns differ in length');
	}

	return {
		id: data.id,
		from: dateAttribute(attributes, 'from'),
		to: dateAttribute(attributes, 'to'),
		resolution,
		tier: tier as HistoryTier,
		unit: typeof attributes.unit === 'string' ? attributes.unit : null,
		dataType: typeof attributes.data_type === 'string' ? attributes.data_type : '',
		columns: { time, min, max, avg, last },
	};
};

/**
 * Whether the series has nothing to draw: no point, or only gaps
 *
 * @param {IHistorySeries | null} series
 */
export const isEmptySeries = (series: IHistorySeries | null): boolean => {
	if (series === null) {
		return true;
	}

	const { min, max, avg, last } = series.columns;

	return ![min, max, avg, last].some((column) => column.some((value) => value !== null));
};

/**
 * What went wrong, from the HTTP status of the failed request when there was one
 *
 * @param {number | undefined} status
 * @param {boolean} malformed
 */
export const classifyError = (status: number | undefined, malformed: boolean): HistoryErrorKind => {
	if (malformed) {
		return 'malformed';
	}

	if (status === 404) {
		return 'notFound';
	}

	if (status === 400 || status === 422) {
		return 'invalid';
	}

	return 'unavailable';
};
