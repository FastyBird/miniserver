export interface TouchHorizontalDirectiveBinding {
	value: (pan: TouchHorizontalChanges) => void;
	oldValue?: (pan: TouchHorizontalChanges) => void;
	modifiers: TouchHorizontalDirectiveBindingModifiers;
}

export interface TouchHorizontalDirectiveBindingModifiers {
	vertical?: boolean;
	horizontal?: boolean;
	mouse?: boolean;
	mousePrevent?: boolean;
	mouseMightPrevent?: boolean;
	mouseStop?: boolean;
	mouseAllDir?: boolean;
	prevent?: boolean;
	mightPrevent?: boolean;
	stop?: boolean;
}

export type TouchDirection = 'left' | 'right' | 'up' | 'down';

export interface TouchHorizontalDirectiveContext {
	handler: (pan: TouchHorizontalChanges) => void;
	direction: { all: boolean; horizontal: boolean; vertical: boolean };
	event?: {
		x: number;
		y: number;
		time: number;
		mouse: boolean;
		detected: boolean;
		abort: boolean;
		isFirst: boolean;
		isFinal: boolean;
		lastX: number;
		lastY: number;
	};
	mouseStart: (evt: MouseEvent) => void;
	mouseEnd: (evt: MouseEvent) => void;
	start: (evt: MouseEvent | TouchEvent, mouseEvent?: boolean) => void;
	end: (evt: MouseEvent | TouchEvent) => void;
	move: (evt: MouseEvent | TouchEvent) => void;
	touchTargetObserver?: MutationObserver;
}

export interface TouchHorizontalChanges {
	evt: MouseEvent | TouchEvent;
	position: {
		top: number;
		left: number;
	};
	direction: TouchDirection;
	isFirst: boolean;
	isFinal: boolean;
	isMouse: boolean;
	duration: number;
	distance: {
		x: number;
		y: number;
	};
	offset: {
		x: number;
		y: number;
	};
	delta: {
		x: number;
		y: number;
	};
}

const testPassive = (): boolean => {
	let passive = false;

	try {
		const opts = Object.defineProperty({}, 'passive', {
			get() {
				return { passive: true };
			},
		});

		window.addEventListener(
			'fb_test',
			() => {
				return {};
			},
			opts
		);
		window.removeEventListener(
			'fb_test',
			() => {
				return {};
			},
			opts
		);

		passive = true;
	} catch (e: any) {
		console.log(e);
	}

	return passive;
};

const setObserver = (el: HTMLElement, evt: MouseEvent | TouchEvent, ctx: TouchHorizontalDirectiveContext): void => {
	const { target } = evt;

	ctx.touchTargetObserver = new MutationObserver(() => {
		// @ts-ignore
		if (target !== null && !el.contains(target)) {
			ctx.end(evt);
		}
	});

	ctx.touchTargetObserver.observe(el, { childList: true, subtree: true });
};

const removeObserver = (ctx: TouchHorizontalDirectiveContext): void => {
	if (ctx.touchTargetObserver !== undefined) {
		ctx.touchTargetObserver.disconnect();
		ctx.touchTargetObserver = undefined;
	}
};

const position = (e: MouseEvent | TouchEvent): { top: number; left: number } => {
	if ('touches' in e && e.touches && e.touches[0]) {
		return {
			top: e.touches[0].clientY,
			left: e.touches[0].clientX,
		};
	} else if ('changedTouches' in e && e.changedTouches && e.changedTouches[0]) {
		return {
			top: e.changedTouches[0].clientY,
			left: e.changedTouches[0].clientX,
		};
	}

	return {
		top: 'clientY' in e ? e.clientY : 0,
		left: 'clientX' in e ? e.clientX : 0,
	};
};

const getDirection = (mod: TouchHorizontalDirectiveBindingModifiers): { all: boolean; horizontal: boolean; vertical: boolean } => {
	const none = mod.horizontal !== true && mod.vertical !== true;

	return {
		all: none || (mod.horizontal === true && mod.vertical === true),
		horizontal: mod.horizontal === true || none,
		vertical: mod.vertical === true || none,
	};
};

const processChanges = (evt: MouseEvent | TouchEvent, ctx: TouchHorizontalDirectiveContext, isFinal: boolean): TouchHorizontalChanges => {
	const pos = position(evt);
	let direction: TouchDirection;
	const distX = pos.left - (ctx.event ? ctx.event.x : 0);
	const distY = pos.top - (ctx.event ? ctx.event.y : 0);
	const absDistX = Math.abs(distX);
	const absDistY = Math.abs(distY);

	if (ctx.direction.horizontal && !ctx.direction.vertical) {
		direction = distX < 0 ? 'left' : 'right';
	} else if (!ctx.direction.horizontal && ctx.direction.vertical) {
		direction = distY < 0 ? 'up' : 'down';
	} else if (absDistX >= absDistY) {
		direction = distX < 0 ? 'left' : 'right';
	} else {
		direction = distY < 0 ? 'up' : 'down';
	}

	return {
		evt,
		position: pos,
		direction,
		isFirst: ctx.event ? ctx.event.isFirst : false,
		isFinal,
		isMouse: ctx.event ? ctx.event.mouse : false,
		duration: new Date().getTime() - (ctx.event ? ctx.event.time : 0),
		distance: {
			x: absDistX,
			y: absDistY,
		},
		offset: {
			x: distX,
			y: distY,
		},
		delta: {
			x: pos.left - (ctx.event ? ctx.event.lastX : 0),
			y: pos.top - (ctx.event ? ctx.event.lastY : 0),
		},
	};
};

const shouldTrigger = (ctx: TouchHorizontalDirectiveContext, changes: TouchHorizontalChanges): boolean | undefined => {
	if (ctx.direction.horizontal && ctx.direction.vertical) return true;

	if (ctx.direction.horizontal && !ctx.direction.vertical) return Math.abs(changes.delta.x) > 0;

	if (!ctx.direction.horizontal && ctx.direction.vertical) return Math.abs(changes.delta.y) > 0;

	return undefined;
};

const TouchHorizontalPan = {
	name: 'touch-pan',

	mounted(el: HTMLElement, binding: TouchHorizontalDirectiveBinding): void {
		const mouse = binding.modifiers.mouse === true;
		const mouseEvtPassive = binding.modifiers.mouseMightPrevent !== true && binding.modifiers.mousePrevent !== true;
		const mouseEvtOpts = testPassive() ? true : { passive: mouseEvtPassive, capture: true };
		const touchEvtOpts = testPassive() ? true : { capture: true };

		const handleEvent = (evt: MouseEvent | TouchEvent, mouseEvent: boolean): void => {
			if (mouse && mouseEvent) {
				if (binding.modifiers.mouseStop) evt.stopPropagation();
				if (binding.modifiers.mousePrevent) evt.preventDefault();
			} else {
				if (binding.modifiers.stop) evt.stopPropagation();
				if (binding.modifiers.prevent) evt.preventDefault();
			}
		};

		const ctx: TouchHorizontalDirectiveContext = {
			handler: binding.value,
			direction: getDirection(binding.modifiers),
			event: undefined,

			mouseStart(evt: MouseEvent): void {
				if (evt.button === 0) {
					document.addEventListener('mousemove', ctx.move, mouseEvtOpts);
					document.addEventListener('mouseup', ctx.mouseEnd, mouseEvtOpts);

					ctx.start(evt, true);
				}
			},

			mouseEnd(evt: MouseEvent): void {
				document.removeEventListener('mousemove', ctx.move, mouseEvtOpts);
				document.removeEventListener('mouseup', ctx.mouseEnd, mouseEvtOpts);

				ctx.end(evt);
			},

			start(evt: MouseEvent | TouchEvent, mouseEvent?: boolean): void {
				removeObserver(ctx);

				if (mouseEvent !== true) {
					setObserver(el, evt, ctx);
				}

				const pos = position(evt);

				ctx.event = {
					x: pos.left,
					y: pos.top,
					time: new Date().getTime(),
					mouse: mouseEvent === true,
					detected: false,
					abort: false,
					isFirst: true,
					isFinal: false,
					lastX: pos.left,
					lastY: pos.top,
				};
			},

			move(evt: MouseEvent | TouchEvent) {
				if (!ctx.event) {
					return;
				}

				if (ctx.event.abort) {
					return;
				}

				if (ctx.event.detected) {
					handleEvent(evt, ctx.event.mouse);

					const changes = processChanges(evt, ctx, false);

					if (shouldTrigger(ctx, changes)) {
						ctx.handler(changes);
						ctx.event.lastX = changes.position.left;
						ctx.event.lastY = changes.position.top;
						ctx.event.isFirst = false;
					}

					return;
				}

				const pos = position(evt);
				const distX = Math.abs(pos.left - ctx.event.x);
				const distY = Math.abs(pos.top - ctx.event.y);

				if (distX === distY) {
					return;
				}

				ctx.event.detected = true;

				if (!ctx.direction.all && (!ctx.event.mouse || binding.modifiers.mouseAllDir !== true)) {
					ctx.event.abort = ctx.direction.vertical ? distX > distY : distX < distY;
				}

				if (!ctx.event.abort) {
					document.documentElement.style.cursor = 'grabbing';
					document.body.classList.add('swipeout-no-pointer-events');
					document.body.classList.add('swipeout-non-selectable');
				}

				ctx.move(evt);
			},

			end(evt: MouseEvent | TouchEvent) {
				if (!ctx.event) {
					return;
				}

				if (!ctx.event.mouse) {
					removeObserver(ctx);
				}

				document.documentElement.style.cursor = '';
				document.body.classList.remove('swipeout-no-pointer-events');
				document.body.classList.remove('swipeout-non-selectable');

				if (ctx.event.abort || !ctx.event.detected || ctx.event.isFirst) {
					return;
				}

				handleEvent(evt, ctx.event.mouse);

				ctx.handler(processChanges(evt, ctx, true));
			},
		};

		if ('__qtouchpan' in el) {
			Object.assign(el, { __qtouchpan_old: (el as any).__qtouchpan ?? null });
		}

		Object.assign(el, { __qtouchpan: ctx });

		if (mouse) {
			el.addEventListener('mousedown', ctx.mouseStart, mouseEvtOpts);
		}

		el.addEventListener('touchstart', ctx.start, touchEvtOpts);
		el.addEventListener('touchmove', ctx.move, touchEvtOpts);
		el.addEventListener('touchcancel', ctx.end, touchEvtOpts);
		el.addEventListener('touchend', ctx.end, touchEvtOpts);
	},

	updated(el: HTMLElement, { oldValue, value, modifiers }: TouchHorizontalDirectiveBinding): void {
		const ctx = ((el as any).__qtouchpan ?? null) as TouchHorizontalDirectiveContext | null;

		if (ctx === null) {
			return;
		}

		if (oldValue !== value) {
			ctx.handler = value;
		}

		if (modifiers.horizontal !== ctx.direction.horizontal || modifiers.vertical !== ctx.direction.vertical) {
			ctx.direction = getDirection(modifiers);
		}
	},

	unmounted(el: HTMLElement, binding: TouchHorizontalDirectiveBinding): void {
		const ctx = ((el as any).__qtouchpan_old ?? (el as any).__qtouchpan) as TouchHorizontalDirectiveContext | undefined;

		if (ctx !== undefined) {
			removeObserver(ctx);

			document.documentElement.style.cursor = '';
			document.body.classList.remove('swipeout-no-pointer-events');
			document.body.classList.remove('swipeout-non-selectable');

			const mouse = binding.modifiers.mouse === true;
			const mouseEvtPassive = binding.modifiers.mouseMightPrevent !== true && binding.modifiers.mousePrevent !== true;
			const mouseEvtOpts = testPassive() ? true : { passive: mouseEvtPassive, capture: true };
			const touchEvtOpts = testPassive() ? true : { capture: true };

			if (mouse) {
				el.removeEventListener('mousedown', ctx.mouseStart, mouseEvtOpts);

				document.removeEventListener('mousemove', ctx.move, mouseEvtOpts);
				document.removeEventListener('mouseup', ctx.mouseEnd, mouseEvtOpts);
			}

			el.removeEventListener('touchstart', ctx.start, touchEvtOpts);
			el.removeEventListener('touchmove', ctx.move, touchEvtOpts);
			el.removeEventListener('touchcancel', ctx.end, touchEvtOpts);
			el.removeEventListener('touchend', ctx.end, touchEvtOpts);

			// @ts-ignore
			delete el['__qtouchpan_old'];
			// @ts-ignore
			delete el['__qtouchpan'];
		}
	},
};

export default TouchHorizontalPan;
