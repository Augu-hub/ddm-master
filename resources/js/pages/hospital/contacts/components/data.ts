import avatar1 from '@/images/users/avatar-1.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';

export type ContactType = {
    rating: number;
    image: string;
    distance: string;
    name: string;
    specialistIn: string;
    address: string;
    email: string;
};

export const contacts: ContactType[] = [
    {
        rating: 4.5,
        image: avatar2,
        distance: '600m',
        name: 'Dr. Morgan Beck',
        specialistIn: 'Dermatology',
        address: '3544 Rainbow Drive Akron, OH',
        email: 'morganhbeck@rhyta.com',
    },
    {
        rating: 4.4,
        image: avatar3,
        distance: '1.4km',
        name: 'Dr. James D. Roger',
        specialistIn: 'Cardiology',
        address: '1234 Maple Street Springfield , USA',
        email: 'jamesdroger@armyspy.com',
    },
    {
        rating: 4.2,
        image: avatar4,
        distance: '1.1km',
        name: 'Dr. Terry J. Bowers',
        specialistIn: 'Pediatrics',
        address: '1487 Marconi St Pimville 1809',
        email: 'terryjbowers@teleworm.us',
    },
    {
        rating: 4.3,
        image: avatar5,
        distance: '900m',
        name: 'Dr. Carlos McCollum',
        specialistIn: 'Orthopedics',
        address: '2425 Bhoola Road Nahoon 12, USA',
        email: 'carloslmccollum@rhyta.com',
    },
    {
        rating: 4.1,
        image: avatar8,
        distance: '10km',
        name: 'Dr. Erma D. Coffman',
        specialistIn: 'Gastroenterology',
        address: 'Casper Fagel straat 331 NT,  USA',
        email: 'ermadcoffman@jourrapide.com',
    },
    {
        rating: 4.2,
        image: avatar1,
        distance: '6km',
        name: 'Dr. Kelli H. Bailey',
        specialistIn: 'Psychiatry',
        address: 'Sneeuwbes 17 2318 AR  Leiden',
        email: 'kelligbailey@rhyta.com',
    },
    {
        rating: 3.5,
        image: avatar6,
        distance: '2.1km',
        name: 'Dr. Robert A. Camp',
        specialistIn: 'Endocrinology',
        address: '85 Elkview Drive Miami, FL 33128',
        email: 'robertacampbell@armyspy.com',
    },
    {
        rating: 4.4,
        image: avatar7,
        distance: '500m',
        name: 'Dr. Jewel B. Odom',
        specialistIn: 'Ophthalmology',
        address: '1468 Mahlon Street Dunellen, NJ',
        email: 'jewelbodom@armyspy.com',
    },
];
