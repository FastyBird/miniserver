<template>
	<RouterView @toggle-menu="onToggleMenu" />
</template>

<script setup lang="ts">
const onToggleMenu = (): void => {
	console.log('toggle-menu');
};
</script>
