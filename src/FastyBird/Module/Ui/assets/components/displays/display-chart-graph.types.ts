import { IWidget, IWidgetDisplay } from '../../models/types';

export interface IDisplayChartGraphProps {
	widgetId: IWidget['id'];
	// The data source whose history is drawn, null while the widget has none
	dataSourceId: string | null;
	display: Pick<IWidgetDisplay, 'minimumValue' | 'maximumValue' | 'precision' | 'enableMinMax' | 'period'>;
	// A digital sensor changes between states, so it is drawn as steps
	digital?: boolean;
	height?: number;
}
