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

type OrderType = {
    id: string;
    customer: {
        name: string;
        image: string;
    };
    products: {
        name: string;
        quantity: number;
    }[];
    total: number;
    date: string;
    paymentStatus: 'completed' | 'failed' | 'pending';
    status: 'delivered' | 'canceled' | 'ready-to-pick' | 'dispatched';
    url: string;
};

export const orders: OrderType[] = [
    {
        id: 'BM9708',
        customer: {
            name: 'Federico Hand',
            image: avatar8,
        },
        products: [
            {
                name: 'Men White Slim Fit T-shirt',
                quantity: 2,
            },
            {
                name: 'HYPERX Cloud Gaming Headphone',
                quantity: 1,
            },
        ],
        total: 176.41,
        date: 'August 05 2023',
        paymentStatus: 'completed',
        status: 'delivered',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM2484',
        customer: {
            name: 'Raci Lopez',
            image: avatar1,
        },
        products: [
            {
                name: 'Minetta Rattan Swivel Premium Chair',
                quantity: 3,
            },
            {
                name: 'Sleepify Luno 4 Seater Fabric Sofa',
                quantity: 1,
            },
        ],
        total: 2312,
        date: 'November 05 2023',
        paymentStatus: 'failed',
        status: 'canceled',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM2543',
        customer: {
            name: 'James Cantrell',
            image: avatar3,
        },
        products: [
            {
                name: '55 L Laptop Backpack fits upto 16 In.',
                quantity: 4,
            },
            {
                name: 'Men White Slim Fit T-shirt',
                quantity: 2,
            },
        ],
        total: 677.09,
        date: 'March 15 2023',
        paymentStatus: 'pending',
        status: 'ready-to-pick',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM6754',
        customer: {
            name: 'Reginald Brown',
            image: avatar4,
        },
        products: [
            {
                name: 'Sleepify Luno 4 Seater Fabric Sofa',
                quantity: 2,
            },
            {
                name: 'HYPERX Cloud Gaming Headphone',
                quantity: 1,
            },
        ],
        total: 532.98,
        date: 'December 23 2023',
        paymentStatus: 'failed',
        status: 'canceled',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM0863',
        customer: {
            name: 'Stacey Smith',
            image: avatar5,
        },
        products: [
            {
                name: '55 L Laptop Backpack fits upto 16 In...',
                quantity: 1,
            },
        ],
        total: 233.15,
        date: 'August 23 2023',
        paymentStatus: 'completed',
        status: 'canceled',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM8630',
        customer: {
            name: 'Alan Green',
            image: avatar6,
        },
        products: [
            {
                name: 'Navy Blue Over Size T-shirt For Men',
                quantity: 5,
            },
            {
                name: 'Men White Slim Fit T-shirt',
                quantity: 6,
            },
        ],
        total: 772.44,
        date: 'January 01 2024',
        paymentStatus: 'pending',
        status: 'ready-to-pick',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM2584',
        customer: {
            name: 'Linda Nelson',
            image: avatar7,
        },
        products: [
            {
                name: 'Sleepify Luno 4 Seater Fabric Sofa',
                quantity: 2,
            },
        ],
        total: 425.56,
        date: 'October 19 2023',
        paymentStatus: 'completed',
        status: 'delivered',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM7466',
        customer: {
            name: 'Pauline Piaffe',
            image: avatar9,
        },
        products: [
            {
                name: "Jordan Jumpman MVP Men's Shoes Size",
                quantity: 1,
            },
            {
                name: 'Men White Slim Fit T-shirt',
                quantity: 2,
            },
        ],
        total: 754.32,
        date: 'April 15 2023',
        paymentStatus: 'failed',
        status: 'dispatched',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM2565',
        customer: {
            name: 'Ethan Wilder',
            image: avatar10,
        },
        products: [
            {
                name: '55 L Laptop Backpack fits upto 16 In',
                quantity: 3,
            },
            {
                name: 'HYPERX Cloud Gaming Headphonet',
                quantity: 1,
            },
        ],
        total: 533.76,
        date: 'May 21 2023',
        paymentStatus: 'completed',
        status: 'delivered',
        url: '/ecommerce/orders/details',
    },
    {
        id: 'BM6569',
        customer: {
            name: 'Steven Mendoza',
            image: avatar2,
        },
        products: [
            {
                name: "Jordan Jumpman MVP Men's Shoes Size",
                quantity: 1,
            },
            {
                name: 'Navy Blue Over Size T-shirt For Men',
                quantity: 3,
            },
        ],
        total: 423,
        date: 'Jun 02 2023',
        paymentStatus: 'completed',
        status: 'dispatched',
        url: '/ecommerce/orders/details',
    },
];
