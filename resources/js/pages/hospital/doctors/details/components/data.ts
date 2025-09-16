import type { ApexChartType } from '@/types';

type AppointmentType = {
    patientName: string;
    date: string;
    time: string;
    contactNo: string;
    reasonForVisit: string;
};

export const skillsChart: ApexChartType = {
    height: 368,
    type: 'radialBar',
    series: [44, 60, 70, 80],
    options: {
        chart: {
            height: 368,
            type: 'radialBar',
        },
        plotOptions: {
            radialBar: {
                track: {
                    margin: 20,
                    background: 'rgba(170,184,197, 0.2)',
                },
                hollow: {
                    size: '5%',
                },
                dataLabels: {
                    name: {
                        show: true,
                    },
                    value: {
                        show: true,
                    },
                },
            },
        },

        stroke: {
            lineCap: 'round',
        },

        legend: {
            show: true,
            showForSingleSeries: false,
            showForNullSeries: true,
            showForZeroSeries: true,
            position: 'bottom',
            horizontalAlign: 'center',
            floating: false,
            fontSize: '14px',
            fontFamily: 'Helvetica, Arial',
            fontWeight: 400,
            formatter: undefined,
            inverseOrder: false,
            width: undefined,
            height: undefined,
            tooltipHoverFormatter: undefined,
            customLegendItems: [],
            offsetX: 0,
            offsetY: 0,
            labels: {
                colors: undefined,
                useSeriesColors: false,
            },
        },
        colors: ['#313a46', '#f9c45c', '#465dff', '#6ac75a'],
        labels: ['Patient Visit', 'Patient Care', 'Endoscopic', 'Operations'],
        responsive: [
            {
                breakpoint: 380,
                options: {
                    chart: {
                        height: 210,
                    },
                },
            },
        ],
    },
};

export const appointmentList: AppointmentType[] = [
    {
        patientName: 'John Doe',
        date: 'July 1, 2024',
        time: '9:00 AM',
        contactNo: '+(567) 890-1234',
        reasonForVisit: 'Annual Check-up',
    },
    {
        patientName: 'Jane Smith',
        date: 'July 1, 2024',
        time: '9:30 AM',
        contactNo: '+(456) 789-0123',
        reasonForVisit: 'Consultation',
    },
    {
        patientName: 'Mike Johnson',
        date: 'July 1, 2024',
        time: '10:00 AM',
        contactNo: '+(345) 678-9012',
        reasonForVisit: 'Lab Results Review',
    },
    {
        patientName: 'Emily Davis',
        date: 'July 1, 2024',
        time: '10:30 AM',
        contactNo: '+(234) 567-8901',
        reasonForVisit: 'Cardiology Follow-up',
    },
    {
        patientName: 'Sarah Wilson',
        date: 'July 1, 2024',
        time: '11:00 AM',
        contactNo: '+(123) 456-7890',
        reasonForVisit: 'Blood Pressure Check',
    },
];
