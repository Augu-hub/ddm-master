import type { ApexChartType } from '@/types';

export type StatisticType = {
    icon: string;
    label: string;
    value: number;
    prefix?: string;
    suffix?: string;
    growth: number;
    chart: ApexChartType;
    url: string;
};

export type UserType = {
    name: string;
    image?: string;
};

export type TransactionType = {
    id: string;
    user: UserType;
    description: string;
    amount: number;
    timestamp: {
        date: string;
        time: string;
    };
    type: 'credit' | 'debit' | 'other';
    paymentMethod: {
        name?: string;
        image: string;
        lastDigits?: number;
    };
    status: 'success' | 'failed' | 'on-hold';
};

export type ElectronicCardType = {
    owner: {
        name: string;
    };
    lastDigits: number;
    expiryDate: string;
    cvv?: number;
    provider: {
        image: string;
        name?: string;
    };
    balance: number;
    url: string;
    variant: string;
};
