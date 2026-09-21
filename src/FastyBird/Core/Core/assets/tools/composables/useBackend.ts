import { ref } from 'vue';

import axios from 'axios';

import { UseBackend } from './types';

export function useBackend(): UseBackend {
	const pendingRequests = ref<number>(0);

	return {
		pendingRequests,
		axios,
	};
}
