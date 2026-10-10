import { ComputedRef } from 'vue';

import { AxiosResponse } from 'axios';

export interface UseBreakpoints {
	isXSDevice: ComputedRef<boolean>;
	isSMDevice: ComputedRef<boolean>;
	isMDDevice: ComputedRef<boolean>;
	isLGDevice: ComputedRef<boolean>;
	isXLDevice: ComputedRef<boolean>;
	isXXLDevice: ComputedRef<boolean>;
}

export interface UseDarkMode {
	isDark: ComputedRef<boolean>;
	toggleDark: (mode?: boolean) => boolean;
}

export interface UseFlashMessage {
	success: (message: string) => void;
	info: (message: string) => void;
	error: (message: string) => void;
	exception: (exception: Error, errorMessage: string) => void;
	requestError: (response: AxiosResponse, errorMessage: string) => void;
}

export interface UseMenu {
	mainMenuItems: any;
	userMenuItems: any;
}
