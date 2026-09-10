import { AxiosResponse } from 'axios';
import { ElNotification } from 'element-plus';
import get from 'lodash.get';

import { UseFlashMessage } from './types';

export function useFlashMessage(): UseFlashMessage {
	const success = (message: string): void => {
		ElNotification.success(message);
	};

	const info = (message: string): void => {
		ElNotification.info(message);
	};

	const error = (message: string): void => {
		ElNotification.error(message);
	};

	const exception = (exception: Error, errorMessage: string): void => {
		let errorShown = false;

		get(exception, 'response.data.errors', []).forEach((error: any): void => {
			if ('code' in error && parseInt(error.code, 10) === 422) {
				ElNotification.error(get(error, 'detail', ''));

				errorShown = true;
			}
		});

		if (!errorShown && errorMessage !== null) {
			ElNotification.error(errorMessage);
		}
	};

	const requestError = (response: AxiosResponse, errorMessage: string): void => {
		let errorShown = false;

		if (response && Object.prototype.hasOwnProperty.call(response, 'data') && Object.prototype.hasOwnProperty.call(response.data, 'errors')) {
			for (const key in response.data.errors) {
				if (Object.prototype.hasOwnProperty.call(response.data.errors, key) && parseInt(response.data.errors[key].code, 10) === 422) {
					ElNotification.error(response.data.errors[key].detail);

					errorShown = true;
				}
			}
		}

		if (!errorShown && errorMessage !== null) {
			ElNotification.error(errorMessage);
		}
	};

	return {
		success,
		info,
		error,
		exception,
		requestError,
	};
}
