import { ComputedRef, MaybeRefOrGetter, Ref } from 'vue';

import { AxiosResponse } from 'axios';

import { HistoryErrorKind, IHistoryColumns, IHistorySeries } from '../utilities/history';

export interface UseUuid {
	generate: () => string;
	validate: (uuid: string) => boolean;
}

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}

export interface UseBreakpoints {
	isXSDevice: ComputedRef<boolean>;
	isSMDevice: ComputedRef<boolean>;
}

export interface UseDataSourceHistoryOptions {
	widgetId: MaybeRefOrGetter<string | null | undefined>;
	dataSourceId: MaybeRefOrGetter<string | null | undefined>;
	// Seconds, the display's period. Anything the endpoint would refuse falls back to the default.
	period?: MaybeRefOrGetter<number | null | undefined>;
	// Points to ask for, up to the endpoint's limit
	points?: number;
}

export interface UseDataSourceHistoryError {
	kind: HistoryErrorKind;
	cause: unknown;
}

export interface UseDataSourceHistory {
	series: Ref<IHistorySeries | null>;
	columns: ComputedRef<IHistoryColumns | null>;
	// The period in effect, in seconds
	period: ComputedRef<number>;
	loading: Ref<boolean>;
	error: Ref<UseDataSourceHistoryError | null>;
	// Answered, and nothing to draw in it
	empty: ComputedRef<boolean>;
	refresh: () => Promise<void>;
}
