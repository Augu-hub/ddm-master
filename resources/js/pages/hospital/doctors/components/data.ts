export type DoctorType = {
    id: string;
    name: string;
    image: string;
    specialty: string;
    contactNo: string;
    email: string;
    timing: {
        days: string;
        time: string;
    };
    experience: number;
    url: string;
};

import avatar1 from '@/images/users/avatar-1.jpg';
import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

export const doctors: DoctorType[] = [
    {
        id: 'DR0001',
        image: avatar3,
        name: 'Dr. James D. Roger',
        specialty: 'Cardiology',
        contactNo: '+96 303-975-3491',
        email: 'jamesdroger@armyspy.com',
        timing: {
            days: 'Mon-Fri',
            time: '9 AM - 5 PM',
        },
        experience: 14,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0002',
        image: avatar2,
        name: 'Dr. Morgan H. Beck',
        specialty: 'Dermatology',
        contactNo: '+56 408-272-5403',
        email: 'morganhbeck@rhyta.com',
        timing: {
            days: 'Mon-Fri',
            time: '10 AM - 6 PM',
        },
        experience: 9,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0003',
        image: avatar4,
        name: 'Dr. Terry J. Bowers',
        specialty: 'Pediatrics',
        contactNo: '+92 845-693-5084',
        email: 'terryjbowers@teleworm.us',
        timing: {
            days: 'Tue-Sat',
            time: '8 AM - 4 PM',
        },
        experience: 12,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0004',
        image: avatar5,
        name: 'Dr. Carlos McCollum',
        specialty: 'Orthopedics',
        contactNo: '+68 036961 83 22',
        email: 'carloslmccollum@rhyta.com',
        timing: {
            days: 'Mon-Thu',
            time: '9 AM - 5 PM',
        },
        experience: 17,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0005',
        image: avatar6,
        name: 'Dr. Jeanetta D. Hovey',
        specialty: 'Neurology',
        contactNo: '+62 0951 29 41 23',
        email: 'jeanettadhovey@jourrapide.com',
        timing: {
            days: 'Wed-Sat',
            time: '1 PM - 9 PM',
        },
        experience: 10,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0006',
        image: avatar7,
        name: 'Dr. Erma D. Coffman',
        specialty: 'Gastroenterology',
        contactNo: '+44 06588 19 07 95',
        email: 'ermadcoffman@jourrapide.com',
        timing: {
            days: 'Mon-Sat',
            time: '6 AM - 2 PM',
        },
        experience: 6,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0007',
        image: avatar8,
        name: 'Dr. Dorian T. Lackey',
        specialty: 'Oncology',
        contactNo: '+41 02161 72 22 77',
        email: 'doriantlackey@teleworm.us',
        timing: {
            days: 'Fri-Sun',
            time: '9 AM - 5 PM',
        },
        experience: 4,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0008',
        image: avatar9,
        name: 'Dr. Kelli H. Bailey',
        specialty: 'Psychiatry',
        contactNo: '+99 073 38 56 39',
        email: 'kelligbailey@rhyta.com',
        timing: {
            days: 'Mon-Fri',
            time: '8 AM - 4 PM',
        },
        experience: 12,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR0009',
        image: avatar10,
        name: 'Dr. Robert A. Camp',
        specialty: 'Endocrinology',
        contactNo: '+9 08684 81 00 91',
        email: 'robertacampbell@armyspy.com',
        timing: {
            days: 'Tue-Sat',
            time: '9 AM - 5 PM',
        },
        experience: 19,
        url: '/hospital/doctors/details',
    },
    {
        id: 'DR00010',
        image: avatar1,
        name: 'Dr. Jewel B. Odom',
        specialty: 'Ophthalmology',
        contactNo: '+41 0451 67 15 47',
        email: 'jewelbodom@armyspy.com',
        timing: {
            days: 'Mon-Thu',
            time: '10 AM - 6 PM',
        },
        experience: 3,
        url: '/hospital/doctors/details',
    },
];
