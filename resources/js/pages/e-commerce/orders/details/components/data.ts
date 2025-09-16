import product1 from '@/images/products/p-1.png';
import product3 from '@/images/products/p-3.png';
import product4 from '@/images/products/p-4.png';
import product6 from '@/images/products/p-6.png';

export type ProductType = {
    image: string;
    name: string;
    price: number;
    size: string;
};

export const orderItems: ProductType[] = [
    {
        image: product3,
        name: 'Minetta Rattan Swivel Luxury Green Lounge Chair',
        price: 300,
        size: '56L X 63D X 102H CM',
    },
    {
        image: product6,
        name: "Jordan Jumpman MVP Men's Shoes Size",
        price: 400,
        size: '8',
    },
    {
        image: product1,
        name: 'Men White Slim Fit T-shirt',
        price: 70.9,
        size: 'M',
    },
    {
        image: product4,
        name: 'HYPERX Cloud Gaming Headphone',
        price: 230.9,
        size: 'M',
    },
];
