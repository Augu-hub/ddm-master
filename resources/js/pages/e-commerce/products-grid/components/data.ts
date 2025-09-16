import product1 from '@/images/products/p-1.png';
import product2 from '@/images/products/p-2.png';
import product3 from '@/images/products/p-3.png';
import product4 from '@/images/products/p-4.png';
import product5 from '@/images/products/p-5.png';
import product6 from '@/images/products/p-6.png';
import product7 from '@/images/products/p-7.png';
import product8 from '@/images/products/p-8.png';
import type { ProductType } from '@/types/shop';

export type CategoryType = {
    icon: string;
    image?: string;
    name: string;
    totalItems: number;
};

export const categories: CategoryType[] = [
    {
        icon: 'solar:t-shirt-bold-duotone',
        name: 'Fashion Men & Women',
        totalItems: 2120,
    },
    {
        icon: 'solar:sofa-2-bold-duotone',
        name: 'Furniture Sofa & Chair',
        totalItems: 624,
    },
    {
        icon: 'solar:headphones-round-sound-bold-duotone',
        name: 'Electronics Items',
        totalItems: 667,
    },
    {
        icon: 'solar:glasses-bold-duotone',
        name: 'Eye Ware & Sunglasses',
        totalItems: 98,
    },
];

export const products: ProductType[] = [
    {
        id: 1,
        image: product1,
        category: 'Zara Fashion',
        name: 'Men White Slim Fit T-shirt',
        size: ['XS', 'S', 'M'],
        rating: 4.5,
        totalReview: 123000,
        price: 90.99,
        discount: {
            type: 'percent',
            amount: 20,
        },
        tag: 'On Deal',
        url: '',
    },
    {
        id: 2,
        image: product2,
        category: 'Wrogn Bags',
        name: '55 L Laptop Backpack fits upto 16 In...',
        size: ['30L', '40L', '55L'],
        rating: 4.5,
        totalReview: 43000,
        price: 120.99,
        discount: {
            type: 'percent',
            amount: 20,
        },
        url: '',
    },
    {
        id: 3,
        image: product3,
        category: 'Premium Furniture',
        name: 'Minetta Rattan Swivel Premium Chair',
        size: ['56L X 63D X 102H CM'],
        rating: 5,
        totalReview: 23000,
        price: 349.99,
        discount: {
            type: 'percent',
            amount: 25,
        },
        tag: 'On Deal',
        url: '',
    },
    {
        id: 4,
        image: product4,
        category: 'Bose Headphones',
        name: 'HYPERX Cloud Gaming Headphone',
        size: ['S', 'M'],
        rating: 4,
        totalReview: 311000,
        price: 259.99,
        discount: {
            type: 'percent',
            amount: 10,
        },
        url: '',
    },
    {
        id: 5,
        image: product5,
        category: 'Winter Fashion',
        name: 'Men Winter Knitted Sweater',
        size: ['S', 'M', 'XL', 'XXL'],
        rating: 4,
        totalReview: 12000,
        price: 100.99,
        discount: {
            type: 'percent',
            amount: 10,
        },
        tag: 'On sell',
        url: '',
    },
    {
        id: 6,
        image: product6,
        category: 'Nike Foot Ware',
        name: "Jordan Jumpman MVP Men's Shoes Size",
        size: ['7', '8', '8.5', '9', '10'],
        rating: 5,
        totalReview: 200000,
        price: 480.99,
        discount: {
            type: 'amount',
            amount: 80,
        },
        tag: 'On sell',
        url: '',
    },
    {
        id: 7,
        image: product7,
        category: 'CRAFT Furniture',
        name: 'Sleepify Luno 4 Seater Fabric Sofa',
        size: ['117W x 38D x 34H CM'],
        rating: 5,
        totalReview: 120000,
        price: 400.99,
        discount: {
            type: 'amount',
            amount: 50,
        },
        url: '',
    },
    {
        id: 8,
        image: product8,
        category: 'H&M Fashion',
        name: 'Navy Blue Over Size T-shirt For Men',
        size: ['M', 'XL', 'XXL', 'XXXL'],
        rating: 4,
        totalReview: 176000,
        price: 90.99,
        discount: {
            type: 'amount',
            amount: 11,
        },
        tag: 'On sell',
        url: '',
    },
];
