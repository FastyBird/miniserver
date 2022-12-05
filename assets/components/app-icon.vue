<template>
	<font-awesome-icon v-bind="props" />
</template>

<script setup lang="ts">
import { FontAwesomeIcon } from '@fortawesome/vue-fontawesome';

import { AppIconFlipTypes, AppIconPullTypes, AppIconSizeType, AppIconType } from './app-icon.types';

interface IAppIconProps {
	icon: AppIconType;
	border?: boolean;
	fixedWidth?: boolean;
	flip?: AppIconFlipTypes | null;
	mask?: string[] | null;
	listItem?: boolean;
	pull?: AppIconPullTypes | null;
	pulse?: boolean;
	rotation?: string | number | null;
	swapOpacity?: boolean;
	size?: AppIconSizeType | null;
	spin?: boolean;
	transform?: string | { [key: string]: any } | null;
	symbol?: string | boolean;
	title?: string | null;
	inverse?: boolean;
}

const props = withDefaults(defineProps<IAppIconProps>(), {
	border: false,
	fixedWidth: false,
	flip: null,
	mask: null,
	listItem: false,
	pull: null,
	pulse: false,
	rotation: null,
	swapOpacity: false,
	size: null,
	spin: false,
	transform: null,
	symbol: false,
	title: null,
	inverse: false,
});
</script>
