export type PatientType = {
    id: string;
    name: string;
    image?: string;
    dob: string;
    gender: 'male' | 'female' | 'other';
    bloodGroup: 'A+' | 'A-' | 'B+' | 'B-' | 'AB+' | 'AB-' | 'O+' | 'O-';
    contactNo: string;
    address: string;
    primaryCarePhysician: {
        name: string;
    };
    url: string;
};

import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

export const patientsList: PatientType[] = [
    {
        id: 'PS49201',
        image: avatar2,
        name: 'Ernest J. Johnson',
        dob: '1 January 1980',
        gender: 'male',
        bloodGroup: 'A+',
        contactNo: '+ (901) 234.5678',
        address: '123 Main St, City, ST',
        primaryCarePhysician: { name: 'Dr. James D. Roger' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS49202',
        image: avatar3,
        name: 'Joseph A. Hill',
        dob: '2 February 1975',
        gender: 'male',
        bloodGroup: 'O+',
        contactNo: '+ 890 123 4567',
        address: '456 Elm St, City, ST',
        primaryCarePhysician: { name: 'Dr. Morgan H. Beck' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS38671',
        name: 'Debra G. Justice',
        dob: '1 May 1989',
        gender: 'female',
        bloodGroup: 'A-',
        contactNo: '+ 789-012-3456',
        address: '789 Pine St, City, ST',
        primaryCarePhysician: { name: 'Dr. Terry J. Bowers' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS48293',
        image: avatar5,
        name: 'Steve A. Howell',
        dob: '4 April 1985',
        gender: 'male',
        bloodGroup: 'B+',
        contactNo: '+44 7890 123456',
        address: '101 Maple St, City, ST',
        primaryCarePhysician: { name: 'Dr. James D. Roger' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS89722',
        image: avatar6,
        name: 'John K. Ewing',
        dob: '5 May 1982',
        gender: 'male',
        bloodGroup: 'AB-',
        contactNo: '+ 678/901-2345',
        address: '202 Oak St, City, ST',
        primaryCarePhysician: { name: 'Dr. Kelli H. Bailey' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS89512',
        image: avatar7,
        name: 'Kathleen R. Stewart',
        dob: '6 June 1978',
        gender: 'male',
        bloodGroup: 'O-',
        contactNo: '+1-567-890-1234',
        address: '303 Cedar St, City, ST',
        primaryCarePhysician: { name: 'Dr. Terry J. Bowers' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS00892',
        image: avatar8,
        name: 'Debra R. Morgan',
        dob: '7 July 1987',
        gender: 'female',
        bloodGroup: 'A+',
        contactNo: '+ (456) 789 0123',
        address: '404 Birch St, City, ST',
        primaryCarePhysician: { name: 'Dr. Kelli H. Bailey' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS54311',
        image: avatar9,
        name: 'Mark J. Scott',
        dob: '8 August 1981',
        gender: 'male',
        bloodGroup: 'B-',
        contactNo: '+ 345 678 9012',
        address: '505 Walnut St, City, ST',
        primaryCarePhysician: { name: 'Dr. Carlos McCollum' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS71434',
        image: avatar10,
        name: 'Connie R. Kilmer',
        dob: '9 September 1979',
        gender: 'female',
        bloodGroup: 'AB+',
        contactNo: '+ 234.567.8901',
        address: '606 Spruce St, City, ST',
        primaryCarePhysician: { name: 'Dr. Morgan H. Beck' },
        url: '/hospital/patients/details',
    },
    {
        id: 'PS63551',
        name: 'Paul K. Coyle',
        dob: '10 October 1983',
        gender: 'female',
        bloodGroup: 'O+',
        contactNo: '+ 123-456-7890',
        address: '707 Redwood St, City, ST',
        primaryCarePhysician: { name: 'Dr. Kelli H. Bailey' },
        url: '/hospital/patients/details',
    },
];
