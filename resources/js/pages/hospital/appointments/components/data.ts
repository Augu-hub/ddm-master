import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

export type StatisticType = {
    label: string;
    value: number;
    icon?: string;
    prefix?: string;
    suffix?: string;
    duration?: 'today' | 'week' | 'month' | 'year';
    subStatistic?: StatisticType[];
};

type AppointmentType = {
    patient: {
        name: string;
        image?: string;
        age: number;
        contactNo: string;
    };
    department: string;
    doctor: {
        image: string;
        name: string;
    };
    date: string;
    time: string;
};

export const statistics: StatisticType[] = [
    {
        icon: 'solar:document-medicine-bold',
        label: 'Total Appointment',
        value: 152,
        duration: 'today',
        subStatistic: [
            {
                label: 'Dermatology',
                value: 67,
                suffix: 'Patients',
            },
            {
                label: 'Cardiology',
                value: 23,
                suffix: 'Patients',
            },
        ],
    },
    {
        icon: 'solar:close-square-bold',
        label: 'Appointment Cancelled',
        value: 67,
        duration: 'today',
        subStatistic: [
            {
                label: 'Gastroenterology',
                value: 56,
                suffix: 'Patients',
            },
            {
                label: 'Nephrology',
                value: 11,
                suffix: 'Patients',
            },
        ],
    },
    {
        icon: 'solar:calendar-date-bold',
        label: 'Appointment Pending',
        value: 201,
        duration: 'today',
        subStatistic: [
            {
                label: 'Oncology',
                value: 132,
                suffix: 'Patients',
            },
            {
                label: 'Orthopedics',
                value: 59,
                suffix: 'Patients',
            },
        ],
    },
    {
        icon: 'solar:user-heart-bold',
        label: 'Total Patient',
        value: 134,
        duration: 'today',
        subStatistic: [
            {
                label: 'Ophthalmology',
                value: 100,
                suffix: 'Patients',
            },
            {
                label: 'Endocrinology',
                value: 34,
                suffix: 'Patients',
            },
        ],
    },
];

export const appointmentsList: AppointmentType[] = [
    {
        patient: {
            name: 'Alice Johnson',
            age: 29,
            contactNo: '+123456789',
        },
        department: 'Cardiology',
        doctor: {
            image: avatar2,
            name: 'Dr.Michael Smith',
        },
        date: '21/07/2024',
        time: '09:00 am',
    },
    {
        patient: {
            name: 'Bob Harris',
            age: 42,
            contactNo: '+123456789',
        },
        department: 'Dermatology',
        doctor: {
            image: avatar3,
            name: 'Dr.Susan Lee',
        },
        date: '21/07/2024',
        time: '10:00 am',
    },
    {
        patient: {
            name: 'Charlie Brown',
            age: 35,
            contactNo: '+123456789',
        },
        department: 'Pediatrics',
        doctor: {
            image: avatar5,
            name: 'Dr.Rachel Adams',
        },
        date: '22/07/2024',
        time: '11:00 am',
    },
    {
        patient: {
            name: 'Donna Green',
            age: 47,
            contactNo: '+123456789',
        },
        department: 'Orthopedics',
        doctor: {
            image: avatar9,
            name: 'Dr.Mark Johnson',
        },
        date: '22/07/2024',
        time: '01:00 pm',
    },
    {
        patient: {
            name: 'Eric Miles',
            age: 52,
            contactNo: '+123456789',
        },
        department: 'Neurology',
        doctor: {
            image: avatar6,
            name: 'Dr.Julia Clark',
        },
        date: '23/07/2024',
        time: '02:00 pm',
    },
    {
        patient: {
            name: 'Fiona Davies',
            age: 31,
            contactNo: '+123456789',
        },
        department: 'Gastroenterology',
        doctor: {
            image: avatar5,
            name: 'Dr.James Martin',
        },
        date: '23/07/2024',
        time: '03:00 pm',
    },
    {
        patient: {
            name: 'George Lewis',
            age: 39,
            contactNo: '+123456789',
        },
        department: 'Oncology',
        doctor: {
            image: avatar3,
            name: 'Dr.Elizabeth Brown',
        },
        date: '24/07/2024',
        time: '04:00 pm',
    },
    {
        patient: {
            name: 'Hannah Carter',
            age: 45,
            contactNo: '+123456789',
        },
        department: 'Psychiatry',
        doctor: {
            image: avatar4,
            name: 'Dr.David Wilson',
        },
        date: '24/07/2024',
        time: '05:00 pm',
    },
];
