import { computed, onBeforeUnmount, ref, toValue, watch } from 'vue';

import axios from 'axios';

import { ModulePrefix } from '@fastybird/miniserver-core';

import {
	HISTORY_DEFAULT_POINTS,
	HistoryParseError,
	IHistoryColumns,
	IHistorySeries,
	classifyError,
	historyQuery,
	isEmptySeries,
	normalizePeriod,
	parseHistory,
} from '../utilities/history';

import { UseDataSourceHistory, UseDataSourceHistoryOptions } from './types';

/**
 * History of the property a data source shows
 *
 * It fetches again when the widget, the data source or the period changes, and drops the answer of a request that has
 * been replaced by a newer one. The live tail is not part of it: the data source model does not name a property yet.
 *
 * @param {UseDataSourceHistoryOptions} options
 */
export function useDataSourceHistory(options: UseDataSourceHistoryOptions): UseDataSourceHistory {
	const series = ref<IHistorySeries | null>(null);
	const loading = ref<boolean>(false);
	const error = ref<UseDataSourceHistory['error']['value']>(null);

	let controller: AbortController | null = null;

	const period = computed<number>((): number => normalizePeriod(options.period === undefined ? null : toValue(options.period)));

	const abort = (): void => {
		controller?.abort();
		controller = null;
	};

	const refresh = async (): Promise<void> => {
		abort();

		const widgetId = toValue(options.widgetId);
		const dataSourceId = toValue(options.dataSourceId);

		if (!widgetId || !dataSourceId) {
			series.value = null;
			error.value = null;
			loading.value = false;

			return;
		}

		const current = new AbortController();

		controller = current;

		loading.value = true;
		error.value = null;

		try {
			const response = await axios.get<unknown>(`/${ModulePrefix.UI}/v1/widgets/${widgetId}/data-sources/${dataSourceId}/history`, {
				params: historyQuery(period.value, options.points ?? HISTORY_DEFAULT_POINTS),
				signal: current.signal,
			});

			// A request that was replaced rejects as canceled, so an answer that gets here is the current one
			series.value = parseHistory(response.data);
		} catch (e: unknown) {
			if (current.signal.aborted || axios.isCancel(e)) {
				return;
			}

			// The previous series belongs to another period, showing it under the error would mislead
			series.value = null;

			error.value = {
				kind: classifyError(axios.isAxiosError(e) ? e.response?.status : undefined, e instanceof HistoryParseError),
				cause: e,
			};
		} finally {
			if (controller === current) {
				controller = null;
				loading.value = false;
			}
		}
	};

	watch(
		[(): string | null | undefined => toValue(options.widgetId), (): string | null | undefined => toValue(options.dataSourceId), period],
		(): void => {
			void refresh();
		}
	);

	void refresh();

	onBeforeUnmount(abort);

	return {
		series,
		columns: computed<IHistoryColumns | null>((): IHistoryColumns | null => series.value?.columns ?? null),
		period,
		loading,
		error,
		empty: computed<boolean>((): boolean => series.value !== null && isEmptySeries(series.value)),
		refresh,
	};
}
