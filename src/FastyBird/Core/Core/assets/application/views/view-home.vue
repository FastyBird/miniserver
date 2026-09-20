<template>HOME</template>

<script setup lang="ts">
import { useHead } from '@unhead/vue';

defineEmits<{
	(e: 'toggleMenu'): void;
}>();

useHead({
	title: 'Homepage',
});
</script>
