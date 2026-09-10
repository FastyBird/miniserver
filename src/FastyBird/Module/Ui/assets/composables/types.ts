import { ComputedRef } from 'vue';

import { AxiosResponse } from 'axios';

export interface UseUuid {
	generate: () => string;
	validate: (uuid: string) => boolean;
}

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}

export interface UseBreakpoints {
	isXSDevice: ComputedRef<boolean>;
	isSMDevice: ComputedRef<boolean>;
}
