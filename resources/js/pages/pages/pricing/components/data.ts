export type PricingPlanType = {
    name: string;
    description: string;
    price: number;
    duration?: 'month' | 'year';
    features: {
        icon?: string;
        label: string;
    }[];
    benefits?: string[];
    isPopular?: boolean;
};

export const pricingPlans1: PricingPlanType[] = [
    {
        name: 'Professional',
        description: 'Everything a small team needs',
        price: 18.99,
        duration: 'month',
        features: [
            {
                label: 'Up to 10 people',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Collect data',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Code extensibility',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Custom reports',
                icon: 'ti ti-circle-x-filled',
            },
            {
                label: 'Product Support',
                icon: 'ti ti-circle-x-filled',
            },
            {
                label: 'Activity reporting',
                icon: 'ti ti-circle-x-filled',
            },
        ],
        benefits: ['Cancel anytime.', 'No card required.'],
    },
    {
        name: 'Teams',
        description: 'Foe growing business',
        price: 36.99,
        duration: 'month',
        features: [
            {
                label: 'Up to 10 people',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Collect data',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Code extensibility',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Custom reports',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Product Support',
                icon: 'ti ti-circle-check-filled',
            },
            {
                label: 'Activity reporting',
                icon: 'ti ti-circle-check-filled',
            },
        ],
        benefits: ['Cancel anytime.', 'No card required.'],
    },
];

export const pricingPlans2: PricingPlanType[] = [
    {
        name: 'Solo Plan',
        description: 'Tailored for individual professionals and hobbyists.',
        price: 229,
        features: [
            {
                label: 'Single user license',
            },
            {
                label: 'Access to all components',
            },
            {
                label: 'Lifetime access',
            },
            {
                label: 'Unlimited projects',
            },
            {
                label: 'Customer support',
            },
            {
                label: 'Free updates',
            },
        ],
    },
    {
        isPopular: true,
        name: 'Startup Plan',
        description: 'Best suited for experienced developers and small teams.',
        price: 399,
        features: [
            {
                label: '5 user license',
            },
            {
                label: 'Access to all components',
            },
            {
                label: 'Lifetime access',
            },
            {
                label: 'Unlimited projects',
            },
            {
                label: 'Priority tech support',
            },
            {
                label: 'Customer support',
            },
            {
                label: 'Free updates',
            },
        ],
    },
    {
        name: 'Organization Plan',
        description: 'Ideal for large teams and organization',
        price: 799,
        features: [
            {
                label: '25 user license',
            },
            {
                label: 'Access to all components',
            },
            {
                label: 'Lifetime access',
            },
            {
                label: 'Unlimited projects',
            },
            {
                label: 'Customer support',
            },
            {
                label: 'Free updates',
            },
        ],
    },
];
