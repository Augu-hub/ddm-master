import { currency } from '@/helpers';

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

export type StatisticType = {
    label: string;
    value: number;
    icon?: string;
    growth: number;
    prefix?: string;
    suffix?: string;
    variant?: string;
    duration: 'week' | 'month' | 'year';
};

type InvoiceType = {
    id: string;
    category: string;
    createdOn: string;
    invoiceTo: {
        name: string;
        image: string;
    };
    amount: number;
    dueDate: string;
    status: 'paid' | 'overdue' | 'cancelled' | 'pending';
};

export const statistics: StatisticType[] = [
    {
        label: 'No.of Clients',
        icon: 'solar:users-group-two-rounded-bold-duotone',
        variant: 'secondary',
        value: 9458,
        growth: -6.15,
        duration: 'month',
    },
    {
        label: 'No. of Invoices',
        icon: 'solar:bill-list-bold-duotone',
        variant: 'primary',
        value: 16.75,
        suffix: 'k',
        growth: 26.87,
        duration: 'month',
    },
    {
        label: 'Paid by Clients',
        icon: 'solar:wallet-money-bold-duotone',
        variant: 'warning',
        value: 98.24,
        suffix: 'k',
        prefix: currency,
        growth: 3.51,
        duration: 'month',
    },
    {
        label: 'Pending Invoices',
        icon: 'solar:banknote-2-bold-duotone',
        variant: 'success',
        value: 87.94,
        suffix: '%',
        growth: -1.05,
        duration: 'month',
    },
    {
        label: 'Cancelled Invoices',
        icon: 'solar:bill-cross-bold-duotone',
        variant: 'danger',
        value: 7.11,
        suffix: '%',
        growth: 0.05,
        duration: 'month',
    },
];

export const invoices: InvoiceType[] = [
    {
        id: 'PC1020@20',
        category: 'Fashion',
        createdOn: '12 Apr 2023',
        invoiceTo: {
            image: avatar2,
            name: 'Raul Villa',
        },
        amount: 42430,
        dueDate: '12 Apr 2023',
        status: 'paid',
    },
    {
        id: 'PC1240@25',
        category: 'Electronics',
        createdOn: '14 Apr 2023',
        invoiceTo: {
            image: avatar3,
            name: 'Fae Sims',
        },
        amount: 416,
        dueDate: '24 Apr 2023',
        status: 'overdue',
    },
    {
        id: 'PC1284@32',
        category: 'Mobile Accessories',
        createdOn: '15 Apr 2023',
        invoiceTo: {
            image: avatar4,
            name: 'David Roderick',
        },
        amount: 187,
        dueDate: '25 Apr 2023',
        status: 'paid',
    },
    {
        id: 'PC1279@69',
        category: 'Electronics',
        createdOn: '6 Dec 2023',
        invoiceTo: {
            image: avatar5,
            name: 'James Zavel',
        },
        amount: 165,
        dueDate: '14 Dec 2023',
        status: 'paid',
    },
    {
        id: 'PC1279@69',
        category: 'Electronics',
        createdOn: '1 Jan 2023',
        invoiceTo: {
            image: avatar6,
            name: 'Denese Martin',
        },
        amount: 165,
        dueDate: '14 Jan 2023',
        status: 'cancelled',
    },
    {
        id: 'PC1276@33',
        category: 'Watches',
        createdOn: '2 Dec 2023',
        invoiceTo: {
            image: avatar7,
            name: 'Jack Nunnally',
        },
        amount: 192,
        dueDate: '2 Dec 2023',
        status: 'overdue',
    },
    {
        id: 'PC1278@29',
        category: 'Bags',
        createdOn: '12 May 2023',
        invoiceTo: {
            image: avatar8,
            name: 'Margaret Shaw',
        },
        amount: 159,
        dueDate: '24 May 2023',
        status: 'paid',
    },
    {
        id: 'PC1271@96',
        category: "Cloth's",
        createdOn: '21 Jun 2023',
        invoiceTo: {
            image: avatar9,
            name: 'Anthony Williams',
        },
        amount: 259,
        dueDate: '1 July 2023',
        status: 'cancelled',
    },
    {
        id: 'PC1986@65',
        category: 'Sofa',
        createdOn: '12 Aug 2023',
        invoiceTo: {
            image: avatar10,
            name: 'Axie Barnes',
        },
        amount: 259,
        dueDate: '28 Aug 2023',
        status: 'paid',
    },
    {
        id: 'PC1984@38',
        category: 'Shoes',
        createdOn: '8 Aug 2023',
        invoiceTo: {
            image: avatar1,
            name: 'Glen Morning',
        },
        amount: 256,
        dueDate: '30 Aug 2023',
        status: 'pending',
    },
];
