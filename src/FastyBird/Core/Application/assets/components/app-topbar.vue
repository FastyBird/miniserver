<template>
	<el-header
		:class="ns.b()"
		class="flex flex-row items-center justify-between px-2 h-[56px]"
	>
		<div class="flex flex-row items-center gap-5">
			<el-button
				type="primary"
				circle
				link
				@click="onToggleMenu"
			>
				<template #icon>
					<Icon icon="fa6-solid:bars" />
				</template>
			</el-button>

			<div :id="FB_BREADCRUMBS_TARGET" />
		</div>

		<div class="flex flex-row items-center gap-5">
			<div @click.stop="onSwitchTheme">
				<el-switch
					v-model="darkMode"
					:before-change="beforeThemeChange"
				>
					<template #active-action>
						<Icon
							icon="fa6-solid:moon"
							class="dark-icon h-[14px]"
						/>
					</template>
					<template #inactive-action>
						<Icon
							icon="fa6-solid:sun"
							class="light-icon h-[14px]"
						/>
					</template>
				</el-switch>
			</div>

			<el-dropdown trigger="click">
				<div class="flex items-center cursor-pointer">
					<app-gravatar
						v-if="userDetails?.email"
						:email="userDetails.email"
						class="w-[32px] rounded-[50%]"
					/>
					<span class="text-14px pl-[5px]">{{ userDetails?.name }}</span>
				</div>

				<template #dropdown>
					<el-dropdown-menu>
						<el-dropdown-item
							v-if="typeof userAccount?.lock === 'function'"
							divided
						>
							<div @click="onLock">
								{{ t('application.userMenu.lockScreen') }}
							</div>
						</el-dropdown-item>
						<el-dropdown-item :divided="typeof userAccount?.lock !== 'function'">
							<div @click="onSignOut">
								{{ t('application.userMenu.signOut') }}
							</div>
						</el-dropdown-item>
					</el-dropdown-menu>
				</template>
			</el-dropdown>
		</div>
	</el-header>
</template>

<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useI18n } from 'vue-i18n';

import { ElButton, ElDropdown, ElDropdownItem, ElDropdownMenu, ElHeader, ElSwitch, useNamespace } from 'element-plus';

import { IAccountManager } from '@fastybird/tools';
import { IAccountDetails, injectAccountManager, useDarkMode } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { AppGravatar, FB_BREADCRUMBS_TARGET } from '../components';

import { IAppTopbarProps } from './app-topbar.types';

defineOptions({
	name: 'AppTopbar',
});

const props = withDefaults(defineProps<IAppTopbarProps>(), {
	menuCollapsed: false,
});

const emit = defineEmits<{
	(e: 'update:menuCollapsed', menuCollapsed: boolean): void;
}>();

const { t } = useI18n();
const ns = useNamespace('app-topbar');

const { isDark, toggleDark } = useDarkMode();

const userAccount: IAccountManager | undefined = injectAccountManager();

const userDetails = computed<IAccountDetails | null>((): IAccountDetails | null => {
	return userAccount?.details.value ?? null;
});

const darkMode = ref<boolean>(isDark.value);

let resolveFn: (value: boolean | PromiseLike<boolean>) => void;

const beforeThemeChange = (): Promise<boolean> => {
	return new Promise((resolve) => {
		resolveFn = resolve;
	});
};

const onSwitchTheme = (event: MouseEvent): void => {
	const isAppearanceTransition =
		// @ts-expect-error
		document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches;

	if (!isAppearanceTransition || !event) {
		resolveFn(true);

		return;
	}

	const x = event.clientX;
	const y = event.clientY;
	const endRadius = Math.hypot(Math.max(x, innerWidth - x), Math.max(y, innerHeight - y));

	const transition = document.startViewTransition(async () => {
		resolveFn(true);

		await nextTick();
	});

	transition.ready.then(() => {
		const clipPath = [`circle(0px at ${x}px ${y}px)`, `circle(${endRadius}px at ${x}px ${y}px)`];

		document.documentElement.animate(
			{
				clipPath: darkMode.value ? [...clipPath].reverse() : clipPath,
			},
			{
				duration: 500,
				easing: 'ease-in',
				pseudoElement: darkMode.value ? '::view-transition-old(root)' : '::view-transition-new(root)',
			}
		);
	});
};

const onToggleMenu = (): void => {
	emit('update:menuCollapsed', !props.menuCollapsed);
};

const onSignOut = (): void => {
	userAccount?.signOut();
};

const onLock = (): void => {
	if (typeof userAccount?.lock === 'function') {
		userAccount?.lock();
	}
};

watch(
	(): boolean => darkMode.value,
	(): void => {
		toggleDark();
	}
);
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@use 'app-topbar.scss';
</style>
