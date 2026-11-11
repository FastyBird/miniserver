import { ChartData } from '../../utilities/chart';

export interface IChartTimeSeriesProps {
	data: ChartData;
	// Decimal places of the labels, null to leave numbers as they are
	precision?: number | null;
	unit?: string | null;
	// Bounds of the value axis, null to follow the data
	minimum?: number | null;
	maximum?: number | null;
	// Draw the line as steps instead of straight segments
	stepped?: boolean;
	// The data holds a lower and an upper edge after the line, and the space between them is shaded
	band?: boolean;
	height?: number;
}
