import type { ApexChartType } from '@/types';

type TreatmentType = {
    id: string;
    type: string;
    date: string;
    result: 'well-done' | 'failed';
    paymentStatus: 'paid' | 'pending';
};

export const checkUpFiles = [
    {
        name: 'Agreement Meditation.zip',
        size: '23.2 MB',
    },
    {
        name: 'Lab Results Document',
        size: '2.7 MB',
    },
    {
        name: 'ECG Report (2 Page)',
        size: '6.7 MB',
    },
    {
        name: 'Cardio-report.pdf',
        size: '4.7 MB',
    },
    {
        name: 'Cardiology-invoice',
        size: '1.2 MB',
    },
];

export const waterChart: ApexChartType = {
    series: [
        {
            data: [2, 3, 2, 7, 4, 2, 3],
        },
    ],
    type: 'bar',
    height: 200,
    options: {
        chart: {
            type: 'bar',
            height: 200,
            sparkline: {
                enabled: true,
            },
        },
        plotOptions: {
            bar: {
                horizontal: false,
                columnWidth: '60%',
                borderRadius: 4,
            },
        },
        colors: ['#465dff'],
        xaxis: {
            categories: ['S', 'M', 'T', 'W', 'T', 'F', 'S'],
            labels: {
                style: {
                    colors: ['#465dff'],
                    fontSize: '14px',
                },
            },
        },
        legend: {
            offsetY: 7,
        },
        grid: {
            row: {
                colors: ['transparent', 'transparent'], // takes an array which will be repeated on columns
                opacity: 0.2,
            },
            borderColor: '#f1f3fa',
        },
    },
};

export const statisticChart: ApexChartType = {
    height: 262,
    type: 'donut',
    series: [4, 7],
    options: {
        chart: {
            height: 262,
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
        labels: ['Analysis 4', 'Visits 7'],
        colors: ['#39afd1', '#ffbc00'],
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

export const treatmentHistory: TreatmentType[] = [
    {
        id: 'ID3524',
        type: 'Surgery',
        date: 'March 3, 2024',
        result: 'well-done',
        paymentStatus: 'pending',
    },
    {
        id: 'ID5723',
        type: 'Physical Therapy',
        date: 'January 28, 2024',
        result: 'well-done',
        paymentStatus: 'paid',
    },
    {
        id: 'ID8563',
        type: 'Chemotherapy',
        date: 'December 1, 2023',
        result: 'well-done',
        paymentStatus: 'paid',
    },
    {
        id: 'ID5233',
        type: 'Radiation Therapy',
        date: 'October 27, 2023',
        result: 'well-done',
        paymentStatus: 'paid',
    },
    {
        id: 'ID4624',
        type: 'Immunotherapy',
        date: 'March 23, 2023',
        result: 'well-done',
        paymentStatus: 'paid',
    },
];
