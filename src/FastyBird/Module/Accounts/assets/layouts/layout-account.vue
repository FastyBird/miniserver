<template>
	<fb-breadcrumbs>
		<el-breadcrumb
			:id="FB_BREADCRUMBS_TARGET"
			separator="/"
		>
			<el-breadcrumb-item :to="{ path: '/' }">
				{{ t('accountsModule.breadcrumbs.homepage') }}
			</el-breadcrumb-item>
			<el-breadcrumb-item :to="{ name: routeNames.account }">
				{{ t('accountsModule.breadcrumbs.account') }}
			</el-breadcrumb-item>
			<el-breadcrumb-item
				v-if="route.name === routeNames.accountProfile"
				:to="{ name: routeNames.accountProfile }"
			>
				{{ t('accountsModule.breadcrumbs.profile') }}
			</el-breadcrumb-item>
			<el-breadcrumb-item
				v-if="route.name === routeNames.accountPassword"
				:to="{ name: routeNames.accountPassword }"
			>
				{{ t('accountsModule.breadcrumbs.security') }}
			</el-breadcrumb-item>
		</el-breadcrumb>
	</fb-breadcrumbs>

	<div class="lt-sm:p-5 sm:p-2">
		<el-page-header
			v-if="isMDDevice"
			@back="onBack"
		>
			<template #content>
				<div class="flex items-center">
					<el-avatar
						:size="32"
						:src="avatarUrl"
						class="mr-3"
					/>

					<span class="text-large font-600 mr-3">{{ t('accountsModule.headings.yourProfile') }}</span>
					<span class="text-sm mr-2">{{ sessionStore.account()?.email?.address || '' }}</span>
				</div>
			</template>

			<template #extra>
				<div class="flex items-center">
					<el-button
						:loading="remoteFormResult === FormResultTypes.WORKING"
						:disabled="remoteFormResult === FormResultTypes.WORKING"
						type="primary"
						@click="onSave"
					>
						{{ t('accountsModule.buttons.save.title') }}
					</el-button>
				</div>
			</template>
		</el-page-header>

		<app-bar-heading
			v-if="!isMDDevice"
			teleport
		>
			<template #icon>
				<el-avatar
					:size="32"
					:src="avatarUrl"
				/>
			</template>

			<template #title>
				{{ t('accountsModule.headings.yourProfile') }}
			</template>

			<template #subtitle>
				{{ sessionStore.account()?.email?.address || '' }}
			</template>
		</app-bar-heading>

		<el-tabs
			v-if="isMDDevice"
			v-model="activeTab"
			class="mt-5"
			@tab-click="onTabClick"
		>
			<el-tab-pane
				:label="t('accountsModule.tabs.profile')"
				:name="'profile'"
			>
				<template #label>
					<span class="flex flex-row items-center gap-2">
						<el-icon><Icon icon="fa6-solid:user" /></el-icon>
						<span>{{ t('accountsModule.tabs.profile') }}</span>
					</span>
				</template>

				<RouterView
					v-if="route.name === routeNames.accountProfile"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
				/>
			</el-tab-pane>

			<el-tab-pane
				:label="t('accountsModule.tabs.security')"
				:name="'security'"
			>
				<template #label>
					<span class="flex flex-row items-center gap-2">
						<el-icon><Icon icon="fa6-solid:lock" /></el-icon>
						<span>{{ t('accountsModule.tabs.security') }}</span>
					</span>
				</template>

				<RouterView
					v-if="route.name === routeNames.accountPassword"
					v-model:remote-form-submit="remoteFormSubmit"
					v-model:remote-form-result="remoteFormResult"
				/>
			</el-tab-pane>
		</el-tabs>

		<RouterView
			v-if="!isMDDevice"
			v-model:remote-form-submit="remoteFormSubmit"
			v-model:remote-form-result="remoteFormResult"
		/>
	</div>
</template>

<script setup lang="ts">
import { computed, onBeforeMount, onMounted, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';
import { RouteRecordName, useRoute, useRouter } from 'vue-router';

import { ElAvatar, ElBreadcrumb, ElBreadcrumbItem, ElButton, ElIcon, ElPageHeader, ElTabPane, ElTabs, TabsPaneContext } from 'element-plus';
import get from 'lodash.get';
import md5 from 'md5';

import { AppBarHeading } from '@fastybird/application';
import { injectStoresManager, useBreakpoints } from '@fastybird/tools';
import { FB_BREADCRUMBS_TARGET, FbBreadcrumbs } from '@fastybird/web-ui-library';
import { Icon } from '@iconify/vue';

import { useRoutesNames } from '../composables';
import { accountsStoreKey, emailsStoreKey, sessionStoreKey } from '../configuration';
import { AccountDocument, EmailDocument, FormResultType, FormResultTypes } from '../types';

type PageTabName = 'profile' | 'security';

defineOptions({
	name: 'LayoutAccount',
});

const router = useRouter();
const route = useRoute();
const { t } = useI18n();

const { routeNames } = useRoutesNames();

const storesManager = injectStoresManager();

const sessionStore = storesManager.getStore(sessionStoreKey);
const accountsStore = storesManager.getStore(accountsStoreKey);
const emailsStore = storesManager.getStore(emailsStoreKey);

const { isMDDevice } = useBreakpoints();
const activeTab = ref<PageTabName>(route.name === routeNames.accountProfile ? 'profile' : 'security');

const mounted = ref<boolean>(false);
const remoteFormSubmit = ref<boolean>(false);
const remoteFormResult = ref<FormResultType>(FormResultTypes.NONE);

const onBack = (): void => {};

const onTabClick = (pane: TabsPaneContext): void => {
	switch (pane.paneName) {
		case 'profile':
			router.push({ name: routeNames.accountProfile });
			break;
		case 'security':
			router.push({ name: routeNames.accountPassword });
			break;
	}
};

const onSave = (): void => {
	remoteFormSubmit.value = true;
};

const avatarUrl = computed<string>((): string => {
	return ['//www.gravatar.com/avatar/', md5((sessionStore.account()?.email?.address || '').trim().toLowerCase()), '?s=80', '&d=retro', '&r=g'].join(
		''
	);
});

onBeforeMount(async (): Promise<void> => {
	const ssrAccountData: AccountDocument | null = get(window, '__ACCOUNTS_MODULE_ACCOUNT__', null);

	if (ssrAccountData !== null) {
		await accountsStore.insertData({
			data: ssrAccountData,
		});
	}

	const ssrEmailsData: EmailDocument[] | null = get(window, '__ACCOUNTS_MODULE_EMAILS__', null);

	if (ssrEmailsData !== null) {
		await emailsStore.insertData({
			data: ssrEmailsData,
		});
	}
});

onMounted((): void => {
	mounted.value = true;
});

watch(
	(): RouteRecordName | string | null | undefined => route.name,
	(val: RouteRecordName | string | null | undefined): void => {
		if (mounted.value) {
			if (val === routeNames.accountProfile && activeTab.value !== 'profile') {
				activeTab.value = 'profile';
			} else if (val === routeNames.accountPassword && activeTab.value !== 'security') {
				activeTab.value = 'security';
			}
		}
	}
);
</script>
