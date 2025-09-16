import type { ApexChartType } from '@/types';
import type { ApexOptions } from 'apexcharts';

import small1 from '@/images/small/small-1.jpg';
import small2 from '@/images/small/small-2.jpg';
import small3 from '@/images/small/small-3.jpg';
import small4 from '@/images/small/small-4.jpg';

const colors1 = ['#727cf5', '#6c757d', '#0acf97', '#fa5c7c', '#e3eaef'];

export const simplePie: ApexChartType = {
    height: 320,
    type: 'pie',
    series: [44, 55, 41, 17, 15],
    options: {
        chart: {
            height: 320,
            type: 'pie',
        },

        labels: ['Series 1', 'Series 2', 'Series 3', 'Series 4', 'Series 5'],
        colors: colors1,
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            // verticalAlign: "middle",
            floating: false,
            fontSize: '14px',
            offsetX: 0,
            offsetY: 7,
        },
        responsive: [
            {
                breakpoint: 600,
                options: {
                    chart: {
                        height: 240,
                    },
                    legend: {
                        show: false,
                    },
                },
            },
        ],
    },
};

export const simpleDonut: ApexChartType = {
    height: 320,
    type: 'donut',
    series: [44, 55, 41, 17, 15],
    options: {
        chart: {
            height: 320,
            type: 'donut',
        },

        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            // verticalAlign: 'middle',
            floating: false,
            fontSize: '14px',
            offsetX: 0,
            offsetY: 7,
        },
        labels: ['Series 1', 'Series 2', 'Series 3', 'Series 4', 'Series 5'],
        colors: ['#39afd1', '#ffbc00', '#313a46', '#fa5c7c', '#0acf97'],
        responsive: [
            {
                breakpoint: 600,
                options: {
                    chart: {
                        height: 240,
                    },
                    legend: {
                        show: false,
                    },
                },
            },
        ],
    },
};

export const monochromePie: ApexChartType = {
    height: 320,
    type: 'pie',
    series: [25, 15, 44, 55, 41, 17],
    options: {
        chart: {
            height: 320,
            type: 'pie',
        },
        labels: ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'],
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            // verticalAlign: 'middle',
            floating: false,
            fontSize: '14px',
            offsetX: 0,
            offsetY: 7,
        },
        theme: {
            monochrome: {
                enabled: true,
            },
        },
        responsive: [
            {
                breakpoint: 600,
                options: {
                    chart: {
                        height: 240,
                    },
                    legend: {
                        show: false,
                    },
                },
            },
        ],
    },
};

export const gradientDonut: ApexChartType = {
    height: 320,
    type: 'donut',
    series: [44, 55, 41, 17, 15],
    options: {
        chart: {
            height: 320,
            type: 'donut',
        },
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            // verticalAlign: "middle",
            floating: false,
            fontSize: '14px',
            offsetX: 0,
            offsetY: 7,
        },
        labels: ['Series 1', 'Series 2', 'Series 3', 'Series 4', 'Series 5'],
        colors: colors1,
        responsive: [
            {
                breakpoint: 600,
                options: {
                    chart: {
                        height: 240,
                    },
                    legend: {
                        show: false,
                    },
                },
            },
        ],
        fill: {
            type: 'gradient',
        },
    },
};

export const patternedDonut: ApexChartType = {
    height: 320,
    type: 'donut',
    series: [44, 55, 41, 17, 15],
    options: {
        chart: {
            height: 320,
            type: 'donut',
            dropShadow: {
                enabled: true,
                color: '#111',
                top: -1,
                left: 3,
                blur: 3,
                opacity: 0.2,
            },
        },
        stroke: {
            show: true,
            width: 2,
        },
        colors: ['#39afd1', '#ffbc00', '#313a46', '#fa5c7c', '#0acf97'],
        labels: ['Comedy', 'Action', 'SciFi', 'Drama', 'Horror'],
        dataLabels: {
            dropShadow: {
                blur: 3,
                opacity: 0.8,
            },
        },
        fill: {
            type: 'pattern',
            opacity: 1,
            pattern: {
                // enabled: true,
                style: ['verticalLines', 'squares', 'horizontalLines', 'circles', 'slantedLines'],
            },
        },
        states: {
            hover: {
                // enabled: false
            },
        },
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            // verticalAlign: 'middle',
            floating: false,
            fontSize: '14px',
            offsetX: 0,
            offsetY: 7,
        },
        responsive: [
            {
                breakpoint: 600,
                options: {
                    chart: {
                        height: 240,
                    },
                    legend: {
                        show: false,
                    },
                },
            },
        ],
    },
};

export const imageFill: ApexChartType = {
    height: 320,
    type: 'pie',
    series: [44, 33, 54, 45],
    options: {
        chart: {
            height: 320,
            type: 'pie',
        },
        labels: ['Series 1', 'Series 2', 'Series 3', 'Series 4'],
        colors: ['#39afd1', '#ffbc00', '#3e60d5', '#47ad77'],
        fill: {
            type: 'image',
            opacity: 0.85,
            image: {
                src: [small1, small2, small3, small4],
                width: 25,
                // imagedHeight: 25
            },
        },
        stroke: {
            width: 4,
        },
        dataLabels: {
            enabled: false,
        },
        legend: {
            show: true,
            position: 'bottom',
            horizontalAlign: 'center',
            // verticalAlign: 'middle',
            floating: false,
            fontSize: '14px',
            offsetX: 0,
            offsetY: 7,
        },
        responsive: [
            {
                breakpoint: 600,
                options: {
                    chart: {
                        height: 240,
                    },
                    legend: {
                        show: false,
                    },
                },
            },
        ],
    },
};

export const donutUpdate: ApexOptions = {
    chart: {
        height: 320,
        type: 'donut',
    },
    dataLabels: {
        enabled: false,
    },
    colors: ['#727cf5', '#6c757d', '#0acf97', '#fa5c7c'],
    legend: {
        show: true,
        position: 'bottom',
        horizontalAlign: 'center',
        // verticalAlign: "middle",
        floating: false,
        fontSize: '14px',
        offsetX: 0,
        offsetY: 7,
    },
    responsive: [
        {
            breakpoint: 600,
            options: {
                chart: {
                    height: 240,
                },
                legend: {
                    show: false,
                },
            },
        },
    ],
};
