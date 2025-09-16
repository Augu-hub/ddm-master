import type { AppointmentType, DoctorType, StatisticType } from '@/pages/dashboards/clinic/components/types';
import type { ApexChartType } from '@/types';

import doctor5 from '@/images/users/doctors/dr-five.jpg';
import doctor4 from '@/images/users/doctors/dr-four.jpg';
import doctor1 from '@/images/users/doctors/dr-one.jpg';
import doctor6 from '@/images/users/doctors/dr-six.jpg';
import doctor3 from '@/images/users/doctors/dr-three.jpg';
import doctor2 from '@/images/users/doctors/dr-two.jpg';

export const statistics: StatisticType[] = [
    {
        label: 'Appointments',
        icon: 'ti ti-calendar-week',
        value: 185,
        badge: {
            text: 'Today',
        },
        subStatistic: [
            {
                label: 'New Appointments',
                value: 125,
            },
            {
                label: 'Total Appointments',
                value: 89.5,
                suffix: 'k',
            },
        ],
        url: '',
    },
    {
        label: 'Total Patients',
        icon: 'ti ti-users',
        value: 75.6,
        suffix: 'k',
        subStatistic: [
            {
                label: 'New Patients',
                value: 61,
            },
            {
                label: 'Old Patients',
                value: 75.5,
                suffix: 'k',
            },
        ],
        url: '',
    },
    {
        label: 'Overall Rooms',
        icon: 'ti ti-hospital-circle',
        value: 195,
        badge: {
            text: '14 Rooms available',
        },
        subStatistic: [
            {
                label: 'General Rooms',
                value: 136,
            },
            {
                label: 'Private Rooms',
                value: 59,
            },
        ],
        url: '',
    },
    {
        label: 'Doctors on Duty',
        icon: 'ti ti-stethoscope',
        value: 87,
        subStatistic: [
            {
                label: 'Available Doctors',
                value: 80,
            },
            {
                label: 'On Leave',
                value: 7,
            },
        ],
        url: '',
    },
    {
        label: 'Treatments',
        icon: 'ti ti-health-recognition',
        value: 99.87,
        suffix: 'k',
        subStatistic: [
            {
                label: 'Operations',
                value: 20.69,
                suffix: 'k',
            },
            {
                label: 'General',
                value: 79.18,
                suffix: 'k',
            },
        ],
        url: '',
    },
];

export const patientsStatisticsChart: ApexChartType = {
    height: 332,
    type: 'area',
    series: [
        {
            name: 'Out Patient',
            type: 'bar',
            data: [16, 19, 19, 16, 16, 14, 15, 15, 17, 17, 19, 19, 18, 18, 20, 20, 18, 18, 22, 22, 20, 20, 18, 18, 20, 20, 18, 20, 20, 22],
        },
        {
            name: 'In Patient',
            data: [21, 24, 24, 21, 21, 19, 20, 20, 22, 22, 24, 24, 23, 23, 25, 25, 23, 23, 27, 27, 25, 25, 23, 23, 25, 25, 23, 25, 25, 27],
        },
    ],
    options: {
        chart: {
            type: 'area',
            height: 358,
            toolbar: {
                show: false,
            },
            offsetX: 0,
            offsetY: 2,
        },
        stroke: {
            width: [0, 2],
            dashArray: [5, 0],
        },
        colors: ['#465dff', '#783bff'],
        grid: {
            strokeDashArray: 7,
            padding: {
                top: 0,
                right: -10,
                bottom: 15,
                left: -10,
            },
        },
        xaxis: {
            // type: 'string',
            axisBorder: {
                show: false,
            },
            labels: {
                offsetY: 2,
            },
        },
        yaxis: {
            tickAmount: 3,
            min: 0,
            labels: {
                formatter: function (val: number) {
                    return val + 'k';
                },
                offsetX: -15,
            },
            axisBorder: {
                show: false,
            },
        },
        legend: {
            show: true,
            horizontalAlign: 'center',
            offsetX: 0,
            offsetY: 5,
            markers: {
                size: 9,
            },
            itemMargin: {
                horizontal: 5,
                vertical: 0,
            },
        },
        dataLabels: {
            enabled: false,
        },
        markers: {
            size: 0,
        },
        tooltip: {
            x: {
                format: 'dd MMM yyyy',
            },
        },
        fill: {
            opacity: [1, 0.5],
            type: ['solid', 'gradient'],
            gradient: {
                type: 'vertical',
                inverseColors: false,
                opacityFrom: 0.35,
                opacityTo: 0,
                stops: [0, 80],
            },
        },
        plotOptions: {
            bar: {
                columnWidth: '50%',
                barHeight: '70%',
                borderRadius: 3,
            },
        },
    },
};

export const topDoctors: DoctorType[] = [
    {
        image: doctor1,
        name: 'Dr. Master Gulati',
        specialistIn: 'Dental Specialist',
        overallRating: 5,
        totalReviewCount: 580,
    },
    {
        image: doctor4,
        name: 'Dr. David Wilson',
        specialistIn: 'Ophthalmologist',
        overallRating: 4.3,
        totalReviewCount: 295,
    },
    {
        image: doctor2,
        name: 'Dr. Robert Brown',
        specialistIn: 'General Specialist',
        overallRating: 5,
        totalReviewCount: 405,
    },
    {
        image: doctor5,
        name: 'Dr. Michael Johnson',
        specialistIn: 'Neurologist',
        overallRating: 4.1,
        totalReviewCount: 120,
    },
    {
        image: doctor3,
        name: 'Dr. Emily Davis',
        specialistIn: 'Pediatrician',
        overallRating: 5,
        totalReviewCount: 385,
    },
    {
        image: doctor6,
        name: 'Dr. Alice Smith',
        specialistIn: 'Cardiologist',
        overallRating: 4,
        totalReviewCount: 92,
    },
];

export const genderChart: ApexChartType = {
    height: 277,
    type: 'donut',
    series: [159.5, 148.56, 45.2],
    options: {
        chart: {
            height: 277,
            type: 'donut',
        },
        legend: {
            show: false,
        },
        stroke: {
            width: 0,
        },
        plotOptions: {
            pie: {
                donut: {
                    size: '75%',
                    labels: {
                        show: true,
                        total: {
                            showAlways: true,
                            show: true,
                        },
                    },
                },
            },
        },
        labels: ['Male', 'Female', 'Child'],
        colors: ['#465dff', '#6ac75a', '#67baf1'],
        dataLabels: {
            enabled: false,
        },
        responsive: [
            {
                breakpoint: 480,
                options: {
                    chart: {
                        width: 200,
                    },
                },
            },
        ],
    },
};

export const allAppointmentsTable = {
    header: ['Queue #', 'Name', 'Gender', 'Age', 'Appointment', 'Date / Time', 'Assign Dr.', 'Status', '•••'],
    body: [
        {
            queue: 29,
            name: 'John Anderson',
            gender: 'male',
            age: 38,
            appointmentFor: 'General Checkup',
            date: '29 Jun, 2024',
            time: '11:15 AM',
            assignedDoctor: {
                image: doctor3,
                name: 'Dr. Emily Davis',
            },
            status: 'completed',
        },
        {
            queue: 30,
            name: 'Jane Smith',
            gender: 'female',
            age: 45,
            appointmentFor: 'Annual Physical',
            date: '10 Aug, 2024',
            time: '09:30 AM',
            assignedDoctor: {
                image: doctor1,
                name: 'Dr. Alex Johnson',
            },
            status: 'completed',
        },
        {
            queue: 31,
            name: 'Mark Brown',
            gender: 'male',
            age: 52,
            appointmentFor: 'Follow-up',
            date: '11 Aug, 2024',
            time: '10:00 AM',
            assignedDoctor: {
                image: doctor2,
                name: 'Dr. Laura Thompson',
            },
            status: 'canceled',
        },
        {
            queue: 32,
            name: 'Lisa White',
            gender: 'female',
            age: 34,
            appointmentFor: 'Consultation',
            date: '12 Aug, 2024',
            time: '11:45 AM',
            assignedDoctor: {
                image: doctor3,
                name: 'Dr. Emily Davis',
            },
            status: 'scheduled',
        },
        {
            queue: 33,
            name: 'Tom Clark',
            gender: 'male',
            age: 29,
            appointmentFor: 'Dental Checkup',
            date: '13 Aug, 2024',
            time: '08:00 AM',
            assignedDoctor: {
                image: doctor4,
                name: 'Dr. Michael Brown',
            },
            status: 'completed',
        },
        {
            queue: 34,
            name: 'Susan Green',
            gender: 'female',
            age: 40,
            appointmentFor: 'Wellness Visit',
            date: '14 Aug, 2024',
            time: '10:30 AM',
            assignedDoctor: {
                image: doctor5,
                name: 'Dr. Sarah Lee',
            },
            status: 'canceled',
        },
        {
            queue: 35,
            name: 'Robert Walker',
            gender: 'male',
            age: 55,
            appointmentFor: 'Eye Exam',
            date: '15 Aug, 2024',
            time: '09:00 AM',
            assignedDoctor: {
                image: doctor6,
                name: 'Dr. Anna Martinez',
            },
            status: 'completed',
        },
    ] as AppointmentType[],
};
