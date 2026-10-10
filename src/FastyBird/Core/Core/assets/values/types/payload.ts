export enum SwitchPayload {
	ON = 'switch_on',
	OFF = 'switch_off',
	TOGGLE = 'switch_toggle',
}

export enum CoverPayload {
	OPEN = 'cover_open',
	OPENING = 'cover_opening',
	OPENED = 'cover_opened',
	CLOSE = 'cover_close',
	CLOSING = 'cover_closing',
	CLOSED = 'cover_closed',
	STOP = 'cover_stop',
	STOPPED = 'cover_stopped',
	CALIBRATE = 'cover_calibrate',
	CALIBRATING = 'cover_calibrating',
}

export enum ButtonPayload {
	PRESSED = 'btn_pressed',
	RELEASED = 'btn_released',
	CLICKED = 'btn_clicked',
	DOUBLE_CLICKED = 'btn_double_clicked',
	TRIPLE_CLICKED = 'btn_triple_clicked',
	LONG_CLICKED = 'btn_long_clicked',
	EXTRA_LONG_CLICKED = 'btn_extra_long_clicked',
}
