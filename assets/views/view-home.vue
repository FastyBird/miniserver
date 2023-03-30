<template>HOME</template>

<script setup lang="ts">
import { useMeta } from 'vue-meta';

defineEmits<{
	(e: 'toggleMenu'): void;
}>();

useMeta({
	title: 'Homepage',
});
</script>
