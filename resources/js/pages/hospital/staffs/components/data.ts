import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

type StaffType = {
    image?: string;
    name: string;
    position: string;
    department: string;
    email: string;
    contactNo: string;
    note: string;
};

export const hospitalStaff: StaffType[] = [
    {
        image: avatar3,
        name: 'Dr. James D. Roger',
        position: 'Chief of Surgery / CHO',
        department: 'Cardiology',
        email: 'jamesdroger@armyspy.com',
        contactNo: '+96 303-975-3491',
        note: 'Specializes in robotics',
    },
    {
        image: avatar2,
        name: 'Dr. Morgan H. Beck',
        position: 'Head of Pediatrics',
        department: 'Pediatrics',
        email: 'morganhbeck@rhyta.com',
        contactNo: '+56 408-272-5403',
        note: 'Fluent in Spanish',
    },
    {
        image: avatar4,
        name: 'Charlie Brown',
        position: 'Senior Nurse',
        department: 'Endocrinology',
        email: 'charliebrown@rhyta.com',
        contactNo: '+ 618-324-7774',
        note: 'CPR instructor',
    },
    {
        image: avatar5,
        name: 'Donna Green',
        position: 'Pediatric Nurse',
        department: 'Pediatrics',
        email: 'donnagreen@rhyta.com',
        contactNo: '+ 570-868-2654',
        note: 'Expert in anxiety disorders',
    },
    {
        image: avatar6,
        name: 'Dr. Dorian T. Lackey',
        position: 'Lead Psychiatrist',
        department: 'Psychiatry',
        email: 'doriantlackey@teleworm.us',
        contactNo: '+41 02161 72 22 77',
        note: 'Certified lactation consultant',
    },
    {
        image: avatar7,
        name: 'Fiona Davies',
        position: 'Cardiology Nurse',
        department: 'Cardiology',
        email: 'fiona@teleworm.us',
        contactNo: '+ 330-504-2068',
        note: 'Medical instructor',
    },
    {
        image: avatar8,
        name: 'Dr. Jewel B. Odom',
        position: 'Orthopedic Surgeon',
        department: 'Orthopedics',
        email: 'jewelbodom@armyspy.com',
        contactNo: '+41 0451 67 15 47',
        note: 'Specializes in Orthopedics',
    },
    {
        image: avatar10,
        name: 'Dr. Kelli H. Bailey',
        position: 'Radiologist',
        department: 'Radiology',
        email: 'kelligbailey@armyspy.com',
        contactNo: '+99 073 38 56 39',
        note: 'Major Radiology',
    },
    {
        image: avatar9,
        name: 'Dr. Robert A. Camp',
        position: 'Neurologist',
        department: 'Neurology',
        email: 'robertacampbell@armyspy.com',
        contactNo: '+9 08684 81 00 91',
        note: 'Specializes in IBD',
    },
    {
        name: 'Dr. Erma D. Coffman',
        position: 'Oncologist',
        department: 'Oncology',
        email: 'ermadcoffman@jourrapide.com',
        contactNo: '+44 06588 19 07 95',
        note: 'Expert in anxiety disorders',
    },
];
