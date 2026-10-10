import { computed } from 'vue';

import { useDark, useToggle } from '@vueuse/core';

import { UseDarkMode } from './types';

export function useDarkMode(): UseDarkMode {
	const darkMode = useDark({
		storageKey: 'fb-theme-appearance',
	});

	const isDark = computed<boolean>((): boolean => {
		return darkMode.value;
	});

	const toggleDark = useToggle(darkMode);

	return {
		isDark,
		toggleDark,
	};
}
