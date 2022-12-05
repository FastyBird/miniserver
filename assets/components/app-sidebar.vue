<template>
	<fb-layout-sidebar
		:collapsed="menuCollapsed"
		@collapse="onCollapseMenu"
	>
		<template #header>
			<router-link
				:to="{ name: 'root' }"
				class="fb-app-component-application-sidebar__logo"
			>
				<logo-small v-if="!breakpointXl" />

				<logo v-else />
			</router-link>
		</template>

		<template #content>
			<fb-layout-navigation :name="t('application.menu.root')">
				<fb-layout-navigation-item
					:action="{ name: 'root' }"
					:label="t('application.menu.dashboard')"
					:action-type="FbMenuItemTypes.VUE_LINK"
				>
					<template #icon>
						<app-icon :icon="AppIconType.TACHOMETER_ALT" />
					</template>
				</fb-layout-navigation-item>

				<fb-layout-navigation-item
					:action="{ name: devicesModuleRouteNames.devices }"
					:label="t('application.menu.devices')"
					:action-type="FbMenuItemTypes.VUE_LINK"
					@click="onCollapseMenu"
				>
					<template #icon>
						<app-icon :icon="AppIconType.PLUG" />
					</template>
				</fb-layout-navigation-item>

				<fb-layout-navigation-item
					:action="{ name: devicesModuleRouteNames.connectors }"
					:label="t('application.menu.connectors')"
					:action-type="FbMenuItemTypes.VUE_LINK"
					@click="onCollapseMenu"
				>
					<template #icon>
						<app-icon :icon="AppIconType.ETHERNET" />
					</template>
				</fb-layout-navigation-item>

				<fb-layout-navigation-item
					:action="{ name: 'root' }"
					:label="t('application.menu.triggers')"
					:action-type="FbMenuItemTypes.VUE_LINK"
					@click="onCollapseMenu"
				>
					<template #icon>
						<app-icon :icon="AppIconType.PROJECT_DIAGRAM" />
					</template>
				</fb-layout-navigation-item>
			</fb-layout-navigation>

			<fb-layout-navigation
				:name="t('application.menu.user')"
				class="fb-app-component-application-sidebar__user-navigation"
			>
				<fb-layout-navigation-item
					:label="t('application.userMenu.accountSettings')"
					:action-type="FbMenuItemTypes.BUTTON"
					@click="onAccountEdit"
				>
					<template #icon>
						<app-icon :icon="AppIconType.USER" />
					</template>
				</fb-layout-navigation-item>

				<fb-layout-navigation-item
					:label="t('application.userMenu.passwordChange')"
					:action-type="FbMenuItemTypes.BUTTON"
					@click="onPasswordChange"
				>
					<template #icon>
						<app-icon :icon="AppIconType.LOCK" />
					</template>
				</fb-layout-navigation-item>

				<fb-layout-navigation-divider />

				<fb-layout-navigation-item
					:label="t('application.userMenu.signOut')"
					:action-type="FbMenuItemTypes.BUTTON"
					@click="onSignOut"
				>
					<template #icon>
						<app-icon :icon="AppIconType.SIGN_OUT_ALT" />
					</template>
				</fb-layout-navigation-item>
			</fb-layout-navigation>
		</template>

		<template #footer>
			<fb-layout-user-menu
				v-if="sessionStore.account !== null"
				:name="sessionStore.account.name"
				class="fb-app-component-application-sidebar__user-menu"
			>
				<template #avatar>
					<app-gravatar
						v-if="sessionStore.account.email"
						:email="sessionStore.account.email?.address"
						:size="36"
						:default-img="'mm'"
						:alt="sessionStore.account.name"
					/>
				</template>

				<template #items>
					<fb-layout-user-menu-item
						:label="`Version: ${version}`"
						:action-type="FbMenuItemTypes.TEXT"
					/>
					<fb-layout-user-menu-divider />

					<fb-layout-user-menu-item
						:label="t('application.userMenu.accountSettings')"
						:action-type="FbMenuItemTypes.BUTTON"
						@click="onAccountEdit"
					>
						<template #icon>
							<app-icon :icon="AppIconType.USER" />
						</template>
					</fb-layout-user-menu-item>

					<fb-layout-user-menu-item
						:label="t('application.userMenu.passwordChange')"
						:action-type="FbMenuItemTypes.BUTTON"
						@click="onPasswordChange"
					>
						<template #icon>
							<app-icon :icon="AppIconType.LOCK" />
						</template>
					</fb-layout-user-menu-item>

					<fb-layout-user-menu-divider />

					<fb-layout-user-menu-item
						:label="t('application.userMenu.signOut')"
						:action-type="FbMenuItemTypes.BUTTON"
						@click="onSignOut"
					>
						<template #icon>
							<app-icon :icon="AppIconType.SIGN_OUT_ALT" />
						</template>
					</fb-layout-user-menu-item>
				</template>
			</fb-layout-user-menu>
		</template>
	</fb-layout-sidebar>
</template>

<script setup lang="ts">
import { computed, inject } from 'vue';
import { useI18n } from 'vue-i18n';

import {
	FbLayoutSidebar,
	FbLayoutNavigation,
	FbLayoutNavigationItem,
	FbLayoutNavigationDivider,
	FbLayoutUserMenu,
	FbLayoutUserMenuItem,
	FbLayoutUserMenuDivider,
	FbMenuItemTypes,
} from '@fastybird/web-ui-library';
import { breakpointsBootstrapV5, useBreakpoints } from '@vueuse/core';

import { useSession } from '@fastybird/accounts-module';
import { useRoutesNames as useDevicesModuleRoutesNames } from '@fastybird/devices-module';

import { AppGravatar, AppIcon } from '@/components';
import { AppIconType } from '@/components/types';

import Logo from '@/assets/images/fastybird_row.svg?component';
import LogoSmall from '@/assets/images/fastybird_bird.svg?component';

import { eventBusInjectionKey } from '@/plugins';

import { version } from './../../package.json';

interface IAppSidebarProps {
	menuCollapsed: boolean;
}

withDefaults(defineProps<IAppSidebarProps>(), {
	menuCollapsed: true,
});

const emit = defineEmits<{
	(e: 'update:menuCollapsed', menuCollapsed: boolean): void;
	(e: 'editAccount'): void;
	(e: 'passwordChange'): void;
}>();

const { t } = useI18n();
const sessionStore = useSession();
const { routeNames: devicesModuleRouteNames } = useDevicesModuleRoutesNames();

const breakpoints = useBreakpoints(breakpointsBootstrapV5);

const eventBus = inject(eventBusInjectionKey);

const breakpointXl = computed<boolean>((): boolean => breakpoints.xl.value);

const onCollapseMenu = (): void => {
	emit('update:menuCollapsed', true);
};

const onAccountEdit = (): void => {
	emit('update:menuCollapsed', true);
	emit('editAccount');
};

const onPasswordChange = (): void => {
	emit('update:menuCollapsed', true);
	emit('passwordChange');
};

const onSignOut = (): void => {
	eventBus?.emit('userSigned', 'out');
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@import 'app-sidebar';
</style>
