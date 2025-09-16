export type StatisticType = {
    label: string;
    value: number;
    icon: string;
    growth: number;
    prefix?: string;
    suffix?: string;
    duration?: 'week' | 'month' | 'year';
    variant?: string;
};

export type BrandType = {
    image: string;
    name: string;
    specialistIn: 'clothing' | 'lifestyle' | 'fashion' | 'manufacturing';
    establishedIn: number;
    stores: number;
    products: number;
    status: 'active' | 'inactive';
};

export type ProductType = {
    image: string;
    name: string;
    price: number;
    quantity: number;
    createdAt: string;
    url: string;
    totalEarning: number;
};

export type OrderType = {
    image: string;
    name: string;
    price: number;
    quantity: number;
    status: 'sold' | 'return';
    url: string;
};

export type ActivityType = {
    icon: string;
    name: string;
    description: string;
    timestamp: string;
    url: string;
};
