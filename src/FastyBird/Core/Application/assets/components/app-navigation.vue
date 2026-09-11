<template>
	<el-scrollbar view-class="h-full">
		<el-menu
			:collapse="props.collapsed"
			:default-active="activeIndex"
			:class="[ns.b(), ns.is('collapsed', props.collapsed), ns.is('desktop', isMDDevice), ns.is('phone', !isMDDevice)]"
			class="h-full md:pt-5 xs:pt-4"
			router
		>
			<template v-for="(item, index) in mainMenuItems">
				<el-menu-item-group
					v-if="Object.entries(item.children).length"
					:key="index"
					:class="[ns.e('group'), ns.is('desktop', isMDDevice), ns.is('phone', !isMDDevice)]"
				>
					<template #title>
						<el-icon v-if="item.meta?.icon">
							<component :is="item.meta?.icon" />
						</el-icon>
						{{ typeof item.meta?.title === 'function' ? item.meta?.title() : item.meta?.title }}
					</template>

					<el-menu-item
						v-for="(subItem, subIndex) in item.children"
						:key="subIndex"
						:route="{ name: subItem.name }"
						:index="`${index}-${subIndex}`"
						@click="emit('click')"
					>
						<el-icon v-if="subItem.meta?.icon">
							<component :is="subItem.meta?.icon" />
						</el-icon>
						<template #title>
							{{ typeof subItem.meta?.title === 'function' ? subItem.meta?.title() : subItem.meta?.title }}
						</template>
					</el-menu-item>
				</el-menu-item-group>
			</template>

			<el-menu-item-group
				v-if="!isMDDevice"
				:class="[ns.e('group'), ns.is('phone', !isMDDevice)]"
				class="mt-5"
			>
				<template
					v-if="!props.collapsed"
					#title
				>
					{{ t('application.menu.user') }}
				</template>

				<el-menu-item
					v-for="(item, index) in userMenuItems"
					:key="index"
					:index="item.index"
					@click="item.click"
				>
					<el-icon><component :is="item.icon" /></el-icon>
					<template #title>
						{{ item.title }}
					</template>
				</el-menu-item>
			</el-menu-item-group>

			<el-menu-item-group
				v-if="!isMDDevice"
				:class="[ns.e('group'), ns.is('phone', !isMDDevice)]"
			>
				<el-menu-item
					index="3-1"
					@click="onSignOut"
				>
					<el-icon><Icon icon="fa6-solid:right-from-bracket" /></el-icon>
					<template #title>
						{{ t('application.userMenu.signOut') }}
					</template>
				</el-menu-item>
			</el-menu-item-group>
		</el-menu>
	</el-scrollbar>
</template>

<script setup lang="ts">
import { computed, h } from 'vue';
import type { VNode } from 'vue';
import { useI18n } from 'vue-i18n';
import { useRoute } from 'vue-router';

import { ElIcon, ElMenu, ElMenuItem, ElMenuItemGroup, ElScrollbar, useNamespace } from 'element-plus';

import { injectAccountManager } from '@fastybird/tools';
import { useBreakpoints } from '@fastybird/tools';
import { Icon } from '@iconify/vue';

import { useMenu } from '../composables';

import { IAppNavigationProps } from './app-navigation.types';

const FasLock = (): VNode => h(Icon, { icon: 'fa6-solid:lock' });
const FasUser = (): VNode => h(Icon, { icon: 'fa6-solid:user' });

defineOptions({
	name: 'AppNavigation',
});

const props = withDefaults(defineProps<IAppNavigationProps>(), {
	collapsed: false,
});

const emit = defineEmits<{
	(e: 'click'): void;
}>();

const route = useRoute();

const { t } = useI18n();
const ns = useNamespace('app-navigation');

const { isMDDevice } = useBreakpoints();
const { mainMenuItems } = useMenu();

const userAccount = injectAccountManager();

const userMenuItems = [
	{
		title: t('application.userMenu.accountSettings'),
		icon: FasUser,
		click: (): void => {
			// TODO: Handle action
			emit('click');
		},
		index: '2-1',
	},
	{
		title: t('application.userMenu.passwordChange'),
		icon: FasLock,
		click: (): void => {
			// TODO: Handle action
			emit('click');
		},
		index: '2-2',
	},
];

const activeIndex = computed<string | undefined>((): string | undefined => {
	for (const name of Object.keys(mainMenuItems)) {
		if (route.matched.find((matched) => matched.name === name) !== undefined) {
			if (mainMenuItems[name].children) {
				for (const subName of Object.keys(mainMenuItems[name].children)) {
					if (route.matched.find((matched) => matched.name === subName) !== undefined) {
						return `${name}-${subName}`;
					}
				}
			}

			return `${name}`;
		}
	}

	return undefined;
});

const onSignOut = (): void => {
	userAccount?.signOut();
};
</script>

<style rel="stylesheet/scss" lang="scss" scoped>
@use 'app-navigation.scss';
</style>
