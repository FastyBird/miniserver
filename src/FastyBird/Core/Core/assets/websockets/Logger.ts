import { IWampLogger } from './types';

export class Logger implements IWampLogger {
	private debug = false;
	private prefix = '%cVue-Wamp: ';

	constructor(debug: boolean) {
		this.debug = debug;
	}

	public info(text: string, ...args: any[]): void {
		if (this.debug) {
			window.console.info(`${this.prefix}%c${text}`, 'color: blue; font-weight: 600', 'color: #333333', args);
		}
	}

	public error(text: string, ...args: any[]): void {
		if (this.debug) {
			window.console.error(`${this.prefix}%c${text}`, 'color: red; font-weight: 600', 'color: #333333', args);
		}
	}

	public warn(text: string, ...args: any[]): void {
		if (this.debug) {
			window.console.warn(`${this.prefix}%c${text}`, 'color: yellow; font-weight: 600', 'color: #333333', args);
		}
	}

	public event(text: string, ...args: any[]): void {
		if (this.debug) {
			window.console.info(`${this.prefix}%c${text}`, 'color: blue; font-weight: 600', 'color: #333333', args);
		}
	}
}
