<template>
	<metainfo>
		<template #title="{ content }">
			{{ content ? `${content} | FastyBird IO server` : `FastyBird IO server` }}
		</template>
	</metainfo>

	<el-container
		v-if="!isMDDevice"
		v-loading="loadingOverlay"
		direction="vertical"
		:class="[ns.b()]"
		class="h-full min-h-full max-h-full w-full min-w-full max-w-full"
	>
		<app-bar
			v-if="userAccount?.isSignedIn.value"
			@toggle-menu="onToggleMenu"
		>
			<template #logo>
				<router-link :to="{ name: 'root' }">
					<logo class="fill-white h-[30px]" />
				</router-link>
			</template>
		</app-bar>

		<el-main class="flex-1">
			<router-view />
		</el-main>

		<el-drawer
			v-if="userAccount?.isSignedIn.value"
			v-model="menuState"
			:size="'80%'"
			:class="[ns.e('mobile-menu')]"
		>
			<app-navigation
				:collapsed="mobileMenuCollapsed"
				@click="onToggleMenu"
			/>
		</el-drawer>
	</el-container>

	<el-container
		v-else
		v-loading="loadingOverlay"
		direction="horizontal"
		:class="[ns.b()]"
		class="h-full min-h-full max-h-full w-full min-w-full max-w-full"
	>
		<el-aside
			v-if="userAccount?.isSignedIn.value"
			:class="[ns.e('aside'), { [ns.em('aside', 'collapsed')]: mainMenuCollapsed }]"
			class="bg-menu-background"
		>
			<app-sidebar :menu-collapsed="mainMenuCollapsed" />
		</el-aside>

		<el-container
			direction="vertical"
			class="flex-1 overflow-hidden"
		>
			<app-topbar
				v-if="userAccount?.isSignedIn.value"
				v-model:menu-collapsed="mainMenuCollapsed"
			/>

			<el-main class="flex-1">
				<router-view />
			</el-main>
		</el-container>
	</el-container>
</template>

<script setup lang="ts">
import { onBeforeMount, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { useMeta } from 'vue-meta';
import { useRouter } from 'vue-router';

import { ElAside, ElContainer, ElDrawer, ElMain, useNamespace, vLoading } from 'element-plus';

import { injectAccountManager, useBreakpoints, useEventBus } from '@fastybird/tools';
import { useWampV1Client } from '@fastybird/vue-wamp-v1';

import Logo from './assets/images/fb_row.svg?component';
import { AppBar, AppNavigation, AppSidebar, AppTopbar } from './components';

const router = useRouter();
const ns = useNamespace('app');
const wampV1Client = useWampV1Client();
const { isMDDevice, isXLDevice } = useBreakpoints();

const eventBus = useEventBus();

const userAccount = injectAccountManager();

const loadingOverlay = ref<boolean>(false);
const menuState = ref<boolean>(false);
const mobileMenuCollapsed = ref<boolean>(isXLDevice.value);
const mainMenuCollapsed = ref<boolean>(!isXLDevice.value);

// Processing timer
let overlayTimer: number;

const hideOverlay = (): void => {
	loadingOverlay.value = false;

	window.clearInterval(overlayTimer);
};

const overlayLoadingListener = (status?: number | boolean): void => {
	if (typeof status === 'number') {
		overlayTimer = window.setInterval(hideOverlay, status * 1000);
		loadingOverlay.value = true;
	} else if (typeof status === 'boolean') {
		window.clearInterval(overlayTimer);

		loadingOverlay.value = status;
	} else {
		loadingOverlay.value = !loadingOverlay.value;
	}
};

const userSignStatusListener = async (state: string): Promise<void> => {
	if (state === 'in') {
		await router.push({ name: 'root' });

		wampV1Client.open();
	} else if (state === 'out') {
		wampV1Client.close();

		await router.push({ name: 'accounts_module-sign_in' });
	}
};

const onToggleMenu = (): void => {
	menuState.value = !menuState.value;
};

onBeforeMount((): void => {
	eventBus.register('loadingOverlay', overlayLoadingListener);
	eventBus.register('userSigned', userSignStatusListener);
});

onMounted((): void => {
	if (userAccount?.isSignedIn.value) {
		wampV1Client.open();
	}
});

onBeforeUnmount((): void => {
	eventBus.unregister('loadingOverlay', overlayLoadingListener);
	eventBus.unregister('userSigned', userSignStatusListener);
});

watch(
	(): boolean => isXLDevice.value,
	(val: boolean): void => {
		mainMenuCollapsed.value = !val;
	}
);

useMeta({
	title: 'IoT control',
	meta: [
		{ charset: 'utf-8' },
		{
			name: 'viewport',
			content: 'width=device-width,initial-scale=1.0',
		},
		{
			hid: 'description',
			name: 'description',
			content: __APP_DESCRIPTION__ ?? '',
		},
	],
	link: [
		{
			rel: 'icon',
			type: 'image/x-icon',
			href: '/favicon.ico',
		},
	],
	htmlAttrs: {
		lang: 'en',
	},
});
</script>

<style rel="stylesheet/scss" lang="scss">
@use 'App.scss';
</style>
