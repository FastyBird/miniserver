<template>
	<app-bar-heading
		v-if="!isMDDevice && isPluginsListRoute"
		teleport
	>
		<template #icon>
			<Icon icon="fa6-solid:plug-circle-bolt" />
		</template>

		<template #title>
			{{ t('devicesModule.headings.plugins.allPlugins') }}
		</template>

		<template #subtitle>
			{{ t('devicesModule.subHeadings.plugins.allPlugins', connectorsPluginsWithConnectors.length) }}
		</template>
	</app-bar-heading>

	<app-bar-button
		v-if="!isMDDevice && isPluginsListRoute"
		teleport
		:align="AppBarButtonAlignTypes.LEFT"
		small
		@click="onPluginInstall"
	>
		<span class="uppercase">{{ t('devicesModule.buttons.new.title') }}</span>
	</app-bar-button>

	<div class="flex flex-row h-full w-full">
		<div
			v-if="isLGDevice"
			class="h-full w-[20rem] border-r border-r-solid"
		>
			<plugins-list-plugins
				:items="connectorsPluginsWithConnectors"
				@detail="onPluginOpen"
				@remove="onPluginRemove"
			/>
		</div>

		<div class="flex-grow h-full">
			<template v-if="isPluginsListRoute">
				<plugins-list-plugins
					v-if="!isLGDevice"
					:items="connectorsPluginsWithConnectors"
					@detail="onPluginOpen"
					@remove="onPluginRemove"
				/>

				<plugins-preview-info
					v-else
					v-loading="connectorsLoading"
					:element-loading-text="t('devicesModule.texts.misc.loadingConnectors')"
					:items="connectorsPluginsWithConnectors"
					class="h-full w-full"
					@install-plugin="onPluginInstall"
					@add-connector="onConnectorCreate"
				/>
			</template>

			<router-view
				v-else
				:key="props.plugin"
			/>
		</div>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount } from 'vue';
import { useI18n } from 'vue-i18n';
import { useMeta } from 'vue-meta';
import { useRoute, useRouter } from 'vue-router';

import { vLoading } from 'element-plus';
import { orderBy } from 'natural-orderby';

import { AppBarButton, AppBarButtonAlignTypes, AppBarHeading } from '@fastybird/application';
import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { PluginsListPlugins, PluginsPreviewInfo } from '../components';
import { useConnectors, usePluginActions, useRoutesNames } from '../composables';
import { connectorPlugins } from '../configuration';
import { ApplicationError } from '../errors';
import { IConnector, IConnectorPlugin } from '../types';

import { IViewPluginsProps } from './view-plugins.types';

defineOptions({
	name: 'ViewPlugins',
});

const props = defineProps<IViewPluginsProps>();

const { t } = useI18n();
const route = useRoute();
const router = useRouter();
useMeta({
	title: t('devicesModule.meta.plugins.list.title'),
});

const { isMDDevice, isLGDevice } = useBreakpoints();
const routeNames = useRoutesNames();

const { fetchConnectors, areLoading: connectorsLoading, connectors } = useConnectors();
const pluginActions = usePluginActions();

const isPluginsListRoute = computed<boolean>((): boolean => {
	return route.name === routeNames.plugins;
});

const connectorsPluginsWithConnectors = computed<{ plugin: IConnectorPlugin; connectors: IConnector[] }[]>(
	(): { plugin: IConnectorPlugin; connectors: IConnector[] }[] => {
		return orderBy<IConnectorPlugin>(connectorPlugins, [(v): string => v.name ?? v.type, (v): string => v.type], ['asc'])
			.map((plugin) => ({
				plugin,
				connectors: orderBy<IConnector>(
					connectors.value
						.filter((connector) => !connector.draft)
						.filter((connector) => {
							return plugin.type === connector.type.type;
						}),
					[(v): string => v.name ?? v.identifier, (v): string => v.identifier],
					['asc']
				),
			}))
			.filter((row) => row.connectors.length > 0);
	}
);

const onPluginInstall = (): void => {
	router.push({
		name: routeNames.pluginInstall,
	});
};

const onPluginOpen = (type: IConnectorPlugin['type']): void => {
	router.push({
		name: routeNames.pluginDetail,
		params: {
			plugin: type,
		},
	});
};

const onPluginRemove = (type: IConnectorPlugin['type']): void => {
	pluginActions.remove(type);
};

const onConnectorCreate = (): void => {
	router.push({
		name: routeNames.connectorCreate,
		params: {
			plugin: 'unknown',
		},
	});
};

onBeforeMount((): void => {
	fetchConnectors().catch((e: any): void => {
		throw new ApplicationError('Something went wrong', e, { statusCode: 503, message: 'Something went wrong' });
	});
});
</script>
