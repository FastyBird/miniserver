<template>
	<display-chart-graph
		v-if="widget !== null && display !== null && display.type.type === 'chart-graph'"
		:data-source-id="dataSource?.id ?? null"
		:digital="widget.type.type === 'digital-sensor'"
		:display="display"
		:widget-id="widget.id"
	/>

	<template v-else>
		<span>VIEW TO BE FINISHED</span>
	</template>
</template>

<script setup lang="ts">
import { computed, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { DisplayChartGraph } from '../components';
import { useFlashMessage } from '../composables';
import { useWidgetDataSources, useWidgetDisplay, useWidgets } from '../models';

defineOptions({
	name: 'ViewWidgetDetail',
});

const props = defineProps<{
	id: string;
}>();

const { t } = useI18n();
const flashMessage = useFlashMessage();

const widgetsStore = useWidgets();
const displayStore = useWidgetDisplay();
const dataSourcesStore = useWidgetDataSources();

const widget = computed(() => widgetsStore.findById(props.id));
const display = computed(() => displayStore.findForWidget(props.id)[0] ?? null);
const dataSource = computed(() => dataSourcesStore.findForWidget(props.id)[0] ?? null);

watch(
	() => props.id,
	async (id): Promise<void> => {
		try {
			await widgetsStore.get({ id, refresh: false });

			const loaded = widgetsStore.findById(id);

			if (loaded === null) {
				return;
			}

			await displayStore.get({ widget: loaded, refresh: false });
			await dataSourcesStore.fetch({ widget: loaded, refresh: false });
		} catch (e: unknown) {
			flashMessage.exception(e as Error, t('uiModule.messages.widgetLoadFailed'));
		}
	},
	{ immediate: true }
);
</script>
