import product1 from '@/images/products/p-1.png';
import product14 from '@/images/products/p-14.png';
import product15 from '@/images/products/p-15.png';
import product2 from '@/images/products/p-2.png';
import product3 from '@/images/products/p-3.png';
import product4 from '@/images/products/p-4.png';
import product5 from '@/images/products/p-5.png';
import product6 from '@/images/products/p-6.png';
import product7 from '@/images/products/p-7.png';
import product8 from '@/images/products/p-8.png';
import product9 from '@/images/products/p-9.png';

type ProductType = {
    id: string;
    name: string;
    image: string;
    description: string;
    price: number;
    quantity: number;
    category: string;
    status: 'active' | 'inactive';
};

export const products: ProductType[] = [
    {
        id: 'PRD001',
        image: product1,
        name: 'Men White Slim Fit T-shirt',
        description: '100% cotton t-shirt in white',
        price: 70.9,
        quantity: 890,
        category: 'Fashion',
        status: 'active',
    },
    {
        id: 'PRD002',
        image: product2,
        name: '55 L Laptop Backpack fits upto 16 In..',
        description: 'Durable hiking backpack',
        price: 100.9,
        quantity: 923,
        category: 'Bags',
        status: 'active',
    },
    {
        id: 'PRD003',
        image: product3,
        name: 'Minetta Rattan Swivel Premium Chair',
        description: 'Ergonomic Villa chair',
        price: 300,
        quantity: 231,
        category: 'Furniture',
        status: 'active',
    },
    {
        id: 'PRD004',
        image: product4,
        name: 'HYPERX Cloud Gaming Headphone',
        description: 'Noise-canceling wireless headphones',
        price: 230.9,
        quantity: 413,
        category: 'Headphone',
        status: 'inactive',
    },
    {
        id: 'PRD005',
        image: product5,
        name: 'Men Winter Knitted Sweater',
        description: 'Waterproof windbreaker jacket',
        price: 90,
        quantity: 120,
        category: 'Winter Fashion',
        status: 'active',
    },
    {
        id: 'PRD006',
        image: product6,
        name: "Jordan Jumpman MVP Men's Shoes Size",
        description: 'Comfortable running shoes',
        price: 400,
        quantity: 264,
        category: 'Foot Ware',
        status: 'inactive',
    },
    {
        id: 'PRD007',
        image: product7,
        name: 'Sleepify Luno 4 Seater Fabric Sofa',
        description: 'Modern three-piece sofa set',
        price: 340,
        quantity: 372,
        category: 'Furniture',
        status: 'inactive',
    },
    {
        id: 'PRD008',
        image: product8,
        name: 'Navy Blue Over Size T-shirt For Men',
        description: '100% cotton t-shirt in blue',
        price: 80,
        quantity: 470,
        category: 'Fashion',
        status: 'active',
    },
    {
        id: 'PRD009',
        image: product9,
        name: "Man's Round Dial Wrist Watch",
        description: 'Fitness tracking smartwatch',
        price: 140,
        quantity: 1092,
        category: 'Watches',
        status: 'active',
    },
    {
        id: 'PRD010',
        image: product14,
        name: 'PS 5 Control',
        description: 'Ergonomic gaming ps and multi control',
        price: 230,
        quantity: 523,
        category: 'Games',
        status: 'inactive',
    },
    {
        id: 'PRD011',
        image: product15,
        name: 'Plastic Duckie barry b benson',
        description: 'Bath Duck Toy Mead With Plastic',
        price: 70,
        quantity: 2102,
        category: 'Toy',
        status: 'inactive',
    },
];
