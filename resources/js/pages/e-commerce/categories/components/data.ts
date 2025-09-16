import product1 from '@/images/products/p-1.png';
import product13 from '@/images/products/p-13.png';
import product14 from '@/images/products/p-14.png';
import product15 from '@/images/products/p-15.png';
import product2 from '@/images/products/p-2.png';
import product3 from '@/images/products/p-3.png';
import product4 from '@/images/products/p-4.png';
import product5 from '@/images/products/p-5.png';
import product6 from '@/images/products/p-6.png';
import product7 from '@/images/products/p-7.png';

type BrandType = {
    name: string;
    image?: string;
};

type CategoryType = {
    image: string;
    name: string;
    brands: BrandType[];
    priceRange: { min: number; max: number };
    bestSellingItems: string[];
    rating: number;
    discountAvailable: string;
    status: 'active' | 'inactive';
};

const brands: BrandType[] = [
    { name: 'Nike' },
    { name: 'H&M' },
    { name: 'ZARA' },
    { name: 'Puma' },
    { name: 'Safari' },
    { name: 'Truster' },
    { name: 'IKEA' },
    { name: 'Hamilton Beach' },
    { name: 'Premium' },
    { name: 'Boat' },
    { name: 'Sony' },
    { name: 'Jbl' },
    { name: 'Bose' },
    { name: 'Bata' },
    { name: 'Woodland' },
    { name: 'Rolex' },
    { name: 'Tag' },
    { name: 'Fossil' },
    { name: 'Timex' },
    { name: 'Electronic Arts' },
    { name: 'IGT logo' },
    { name: 'Lego' },
    { name: 'Hot Wheels' },
    { name: 'Fisher-Price' },
];

export const categories: CategoryType[] = [
    {
        image: product1,
        name: 'Fashion',
        brands: [brands[0], brands[1], brands[2], brands[3]],
        priceRange: { min: 200, max: 2000 },
        bestSellingItems: ['T-shirt', 'Cargo Pent', 'Dress'],
        rating: 4.5,
        discountAvailable: 'Seasonal Sales',
        status: 'active',
    },
    {
        image: product2,
        name: 'Bags',
        brands: [brands[0], brands[3], brands[4], brands[5]],
        priceRange: { min: 20, max: 300 },
        bestSellingItems: ['Collage Bags', 'Trolly Bags'],
        rating: 4.1,
        discountAvailable: 'Clearance Sales',
        status: 'active',
    },
    {
        image: product3,
        name: 'Furniture',
        brands: [brands[6], brands[7], brands[8]],
        priceRange: { min: 300, max: 1500 },
        bestSellingItems: ['Sofas', 'Blenders', 'Chairs'],
        rating: 4.5,
        discountAvailable: 'Bundled Discounts',
        status: 'active',
    },
    {
        image: product4,
        name: 'Headphone',
        brands: [brands[9], brands[10], brands[11], brands[12]],
        priceRange: { min: 150, max: 1000 },
        bestSellingItems: ['Gaming Headphone', 'Buds', 'Nakebands'],
        rating: 4.1,
        discountAvailable: 'Holiday Discounts',
        status: 'inactive',
    },
    {
        image: product5,
        name: 'Winter Fashion',
        brands: [brands[0], brands[1], brands[2], brands[3]],
        priceRange: { min: 100, max: 900 },
        bestSellingItems: ['Hoodie'],
        rating: 4.3,
        discountAvailable: 'Bulk Purchase Deals',
        status: 'active',
    },
    {
        image: product6,
        name: 'Foot Ware',
        brands: [brands[0], brands[13], brands[14], brands[3]],
        priceRange: { min: 100, max: 1200 },
        bestSellingItems: ['Sandal', 'Loafer', 'Slippers'],
        rating: 4.1,
        discountAvailable: 'Coupon Codes',
        status: 'inactive',
    },
    {
        image: product7,
        name: 'Furniture',
        brands: [brands[7], brands[8], brands[9]],
        priceRange: { min: 300, max: 2200 },
        bestSellingItems: ['Sofas'],
        rating: 4.8,
        discountAvailable: 'Subscription Discounts',
        status: 'inactive',
    },
    {
        image: product13,
        name: 'Watches',
        brands: [brands[15], brands[16], brands[17], brands[18]],
        priceRange: { min: 400, max: 3000 },
        bestSellingItems: ['Analog Watch', 'Hand Watch'],
        rating: 4.8,
        discountAvailable: 'Coupon Codes',
        status: 'active',
    },
    {
        image: product14,
        name: 'Games',
        brands: [brands[10], brands[19], brands[20]],
        priceRange: { min: 230, max: 2100 },
        bestSellingItems: ['PS5', 'Lego'],
        rating: 4.1,
        discountAvailable: 'Seasonal Sales',
        status: 'active',
    },
    {
        image: product15,
        name: 'Toy',
        brands: [brands[21], brands[22], brands[23]],
        priceRange: { min: 100, max: 800 },
        bestSellingItems: ['Toy Car', 'Lego'],
        rating: 4.3,
        discountAvailable: 'Buy One Get One',
        status: 'inactive',
    },
];
