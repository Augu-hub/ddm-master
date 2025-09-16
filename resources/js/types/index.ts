import type { ApexOptions } from 'apexcharts';

export type ApexChartType = {
    height: number;
    type?: string;
    series: any[];
    options: ApexOptions;
};
