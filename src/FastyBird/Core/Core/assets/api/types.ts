import { Ref } from 'vue';

import { AxiosInstance } from 'axios';

export interface UseBackend {
	pendingRequests: Ref<number>;
	axios: AxiosInstance;
}
