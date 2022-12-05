<template>
	<img
		:src="url"
		:alt="props.alt"
		@load="emit('load', $event)"
		@error="emit('error', $event)"
	/>
</template>

<script setup lang="ts">
import { computed } from 'vue';
import md5 from 'md5';

import { AppGravatarDefaultImg, AppGravatarRating } from './app-gravatar.types';

interface IAppGravatarProps {
	email: string;
	hash?: string | null;
	size?: number;
	defaultImg?: AppGravatarDefaultImg;
	rating?: AppGravatarRating;
	alt?: string;
	protocol?: string | null;
	hostname?: string;
}

const props = withDefaults(defineProps<IAppGravatarProps>(), {
	hash: null,
	size: 80,
	defaultImg: 'retro',
	rating: 'g',
	alt: 'Avatar',
	protocol: null,
	hostname: 'www.gravatar.com',
});

const emit = defineEmits<{
	(e: 'load', event: Event): void;
	(e: 'error', event: Event): void;
}>();

const url = computed<string>((): string => {
	const img = [
		`${props.protocol ? props.protocol : ''}//${props.hostname}/avatar/`,
		props.hash || md5(props.email.trim().toLowerCase()),
		`?s=${props.size}`,
		`&d=${props.defaultImg}`,
		`&r=${props.rating}`,
	];

	return img.join('');
});
</script>
