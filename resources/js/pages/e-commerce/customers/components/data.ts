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

type CustomerType = {
    image: string;
    name: string;
    invoice: string;
    status: 'active' | 'block';
    totalAmount: number;
    amountDue: number;
    shopRate: number;
    dueDate: string;
};

export const customers: CustomerType[] = [
    {
        image: avatar8,
        name: 'Emily Davis',
        invoice: 'INV-103452',
        status: 'active',
        totalAmount: 532.75,
        amountDue: 123.45,
        shopRate: 30,
        dueDate: '19 January 2024',
    },
    {
        image: avatar1,
        name: 'Michael Johnson',
        invoice: 'INV-984321',
        status: 'block',
        totalAmount: 689.5,
        amountDue: 234.56,
        shopRate: 70,
        dueDate: '10 February 2024',
    },
    {
        image: avatar2,
        name: 'Robert Smith',
        invoice: 'INV-567890',
        status: 'active',
        totalAmount: 745.6,
        amountDue: 498.76,
        shopRate: 40,
        dueDate: '04 Jun 2024',
    },
    {
        image: avatar3,
        name: 'David Williams',
        invoice: 'INV-876543',
        status: 'active',
        totalAmount: 812.4,
        amountDue: 345.67,
        shopRate: 60,
        dueDate: '01 April 2024',
    },
    {
        image: avatar4,
        name: 'James Miller',
        invoice: 'INV-192837',
        status: 'block',
        totalAmount: 970.25,
        amountDue: 210.98,
        shopRate: 50,
        dueDate: '01 May 2024',
    },
    {
        image: avatar5,
        name: 'Richard Taylor',
        invoice: 'INV-283746',
        status: 'block',
        totalAmount: 524.8,
        amountDue: 432.1,
        shopRate: 80,
        dueDate: '17 January 2024',
    },
    {
        image: avatar6,
        name: 'Thomas Anderson',
        invoice: 'INV-465728',
        status: 'active',
        totalAmount: 789.9,
        amountDue: 187.65,
        shopRate: 60,
        dueDate: '09 July 2024',
    },
    {
        image: avatar7,
        name: 'Charles Thomas',
        invoice: 'INV-372819',
        status: 'active',
        totalAmount: 632.55,
        amountDue: 321.09,
        shopRate: 80,
        dueDate: '14 Nov 2023',
    },
    {
        image: avatar9,
        name: 'Daniel Harris',
        invoice: 'INV-948576',
        status: 'block',
        totalAmount: 915.3,
        amountDue: 543.21,
        shopRate: 30,
        dueDate: '23 August 2024',
    },
    {
        image: avatar10,
        name: 'Sarah Brown',
        invoice: 'INV-562738',
        status: 'active',
        totalAmount: 750.75,
        amountDue: 321.09,
        shopRate: 70,
        dueDate: '15 July 2024',
    },
];
