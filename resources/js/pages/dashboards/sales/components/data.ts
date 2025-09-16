import { currency } from '@/helpers';
import type { ActivityType, BrandType, OrderType, ProductType, StatisticType } from '@/pages/dashboards/sales/components/types';
import type { ApexChartType } from '@/types';

import logo1 from '@/images/products/logo/logo-1.svg';
import logo4 from '@/images/products/logo/logo-4.svg';
import logo5 from '@/images/products/logo/logo-5.svg';
import logo6 from '@/images/products/logo/logo-6.svg';
import logo8 from '@/images/products/logo/logo-8.svg';

import product1 from '@/images/products/p-1.png';
import product2 from '@/images/products/p-2.png';
import product3 from '@/images/products/p-3.png';
import product4 from '@/images/products/p-4.png';
import product5 from '@/images/products/p-5.png';
import product6 from '@/images/products/p-6.png';
import product7 from '@/images/products/p-7.png';

export const statistics: StatisticType[] = [
    {
        label: 'Total Orders',
        icon: 'solar:case-round-minimalistic-bold-duotone',
        value: 687.3,
        suffix: 'k',
        growth: -9.19,
        duration: 'month',
    },
    {
        label: 'Total Returns',
        icon: 'solar:bill-list-bold-duotone',
        value: 9.62,
        suffix: 'k',
        growth: 26.87,
        duration: 'month',
    },
    {
        label: 'Avg. Sales Earnings',
        icon: 'solar:wallet-money-bold-duotone',
        value: 98.24,
        prefix: currency,
        growth: 3.51,
        duration: 'month',
    },
    {
        label: 'Number of Visits',
        icon: 'solar:eye-bold-duotone',
        value: 87.94,
        suffix: 'M',
        growth: -1.05,
        duration: 'month',
    },
];

export const overviewChart: ApexChartType = {
    height: 285,
    type: 'line',
    series: [
        {
            name: 'Total Income',
            type: 'bar',
            data: [89.25, 98.58, 68.74, 108.87, 77.54, 84.03, 51.24, 28.57, 92.57, 42.36, 88.51, 36.57],
        },
        {
            name: 'Total Expenses',
            type: 'bar',
            data: [22.25, 24.58, 36.74, 22.87, 19.54, 25.03, 29.24, 10.57, 24.57, 35.36, 20.51, 17.57],
        },
        {
            name: 'Investments',
            type: 'area',
            data: [34, 65, 46, 68, 49, 61, 42, 44, 78, 52, 63, 67],
        },
        {
            name: 'Savings',
            type: 'line',
            data: [8, 12, 7, 17, 21, 11, 5, 9, 7, 29, 12, 35],
        },
    ],
    options: {
        chart: {
            height: 285,
            type: 'line',
            toolbar: {
                show: false,
            },
        },
        stroke: {
            dashArray: [0, 0, 0, 8],
            width: [0, 0, 2, 2],
            curve: 'smooth',
        },
        fill: {
            opacity: [1, 1, 0.1, 1],
            type: ['gradient', 'solid', 'solid', 'solid'],
            gradient: {
                type: 'vertical',
                //   shadeIntensity: 1,
                inverseColors: false,
                opacityFrom: 0.5,
                opacityTo: 0,
                stops: [0, 70],
            },
        },
        markers: {
            size: [0, 0, 0, 0],
            strokeWidth: 2,
            hover: {
                size: 4,
            },
        },
        xaxis: {
            categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
            axisTicks: {
                show: false,
            },
            axisBorder: {
                show: false,
            },
        },
        yaxis: {
            stepSize: 25,
            min: 0,
            labels: {
                formatter: function (val) {
                    return val + 'k';
                },
                offsetX: -15,
            },
            axisBorder: {
                show: false,
            },
        },
        grid: {
            show: true,
            xaxis: {
                lines: {
                    show: false,
                },
            },
            yaxis: {
                lines: {
                    show: true,
                },
            },
            padding: {
                top: 0,
                right: -15,
                bottom: 15,
                left: -15,
            },
        },
        legend: {
            show: true,
            horizontalAlign: 'center',
            offsetX: 0,
            offsetY: -5,
            markers: {
                size: 9,
            },
            itemMargin: {
                horizontal: 10,
                vertical: 0,
            },
        },
        plotOptions: {
            bar: {
                columnWidth: '50%',
                barHeight: '70%',
                borderRadius: 3,
            },
        },
        colors: ['#6ac75a', '#465dff', '#783bff', '#f7577e'],
        tooltip: {
            shared: true,
            y: [
                {
                    formatter: function (y: number) {
                        if (typeof y !== 'undefined') {
                            return '$' + y.toFixed(2) + 'k';
                        }
                        return y;
                    },
                },
                {
                    formatter: function (y: number) {
                        if (typeof y !== 'undefined') {
                            return '$' + y.toFixed(2) + 'k';
                        }
                        return y;
                    },
                },
                {
                    formatter: function (y: number) {
                        if (typeof y !== 'undefined') {
                            return '$' + y.toFixed(2) + 'k';
                        }
                        return y;
                    },
                },
                {
                    formatter: function (y: number) {
                        if (typeof y !== 'undefined') {
                            return '$' + y.toFixed(2) + 'k';
                        }
                        return y;
                    },
                },
            ],
        },
    },
};

export const trafficBySourceChart: ApexChartType = {
    height: 330,
    type: 'radialBar',
    series: [44, 55, 67, 22],
    options: {
        chart: {
            height: 330,
            type: 'radialBar',
        },
        plotOptions: {
            // circle: {
            //     dataLabels: {
            //         showOn: 'hover'
            //     }
            // },
            radialBar: {
                track: {
                    margin: 17,
                    background: 'rgba(170,184,197, 0.2)',
                },
                hollow: {
                    size: '1%',
                },
                dataLabels: {
                    name: {
                        show: false,
                    },
                    value: {
                        show: false,
                    },
                },
            },
        },
        stroke: {
            lineCap: 'round',
        },
        colors: ['#465dff', '#6ac75a', '#783bff', '#f7577e'],
        labels: ['Completed', 'In Progress', 'Yet to Start', 'Cancelled'],
        responsive: [
            {
                breakpoint: 380,
                options: {
                    chart: {
                        height: 260,
                    },
                },
            },
        ],
    },
};

export const brandListingTable: BrandType[] = [
    {
        image: logo1,
        name: 'Zaroan - Brazil',
        specialistIn: 'clothing',
        establishedIn: 2020,
        stores: 1500,
        products: 8950,
        status: 'active',
    },
    {
        image: logo4,
        name: 'Jocky-Johns - USA',
        specialistIn: 'clothing',
        establishedIn: 1985,
        stores: 205,
        products: 1258,
        status: 'active',
    },
    {
        image: logo5,
        name: 'Ginne - India',
        specialistIn: 'lifestyle',
        establishedIn: 2001,
        stores: 89,
        products: 338,
        status: 'active',
    },
    {
        image: logo6,
        name: 'DDoen - Brazil',
        specialistIn: 'fashion',
        establishedIn: 1995,
        stores: 650,
        products: 6842,
        status: 'active',
    },
    {
        image: logo8,
        name: 'Zoddiak - Canada',
        specialistIn: 'manufacturing',
        establishedIn: 1963,
        stores: 109,
        products: 952,
        status: 'active',
    },
];

export const topSellingProductsTable: ProductType[] = [
    {
        image: product1,
        name: 'ASOS High Waist Tshirt',
        createdAt: '07 April 2024',
        price: 79.49,
        quantity: 82,
        totalEarning: 6518.18,
        url: '',
    },
    {
        image: product7,
        name: 'Marco Single Sofa',
        createdAt: '25 March 2024',
        price: 128.5,
        quantity: 37,
        totalEarning: 4754.5,
        url: '',
    },
    {
        image: product4,
        name: 'Smart Headphone',
        createdAt: '17 March 2024',
        price: 39.99,
        quantity: 64,
        totalEarning: 2559.36,
        url: '',
    },
    {
        image: product5,
        name: 'Smart Headphone',
        createdAt: '12 March 2024',
        price: 20.0,
        quantity: 184,
        totalEarning: 3680.58,
        url: '',
    },
    {
        image: product6,
        name: 'Marco Shoes',
        createdAt: '05 March 2024',
        price: 28.49,
        quantity: 69,
        totalEarning: 1965.81,
        url: '',
    },
];

export const recentOrders: OrderType[] = [
    {
        image: product6,
        name: 'Marco Shoes',
        price: 29.99,
        quantity: 1,
        status: 'sold',
        url: '',
    },
    {
        image: product1,
        name: 'High Waist Tshirt',
        price: 9.99,
        quantity: 3,
        status: 'sold',
        url: '',
    },
    {
        image: product3,
        name: 'Comfort Chair',
        price: 49.99,
        quantity: 1,
        status: 'return',
        url: '',
    },
    {
        image: product4,
        name: 'Smart Headphone',
        price: 39.99,
        quantity: 1,
        status: 'sold',
        url: '',
    },
    {
        image: product2,
        name: 'Smart Headphone',
        price: 12.99,
        quantity: 4,
        status: 'sold',
        url: '',
    },
];

export const recentActivity: ActivityType[] = [
    {
        icon: 'ti ti-basket',
        name: 'You sold an item',
        description: 'Paul Burgess just purchased “My - Admin Dashboard”!',
        timestamp: '5 minutes ago',
        url: '',
    },
    {
        icon: 'ti ti-rocket',
        name: 'Product on the Theme Market',
        description: 'Reviewer added Admin Dashboard',
        timestamp: '30 minutes ago',
        url: '',
    },
    {
        icon: 'ti ti-message',
        name: 'Robert Delaney',
        description: 'Send you message "Are you there?"',
        timestamp: '2 hours ago',
        url: '',
    },
    {
        icon: 'ti ti-photo',
        name: 'Audrey Tobey',
        description: 'Uploaded a photo "Error.jpg"',
        timestamp: '14 hours ago',
        url: '',
    },
    {
        icon: 'ti ti-basket',
        name: 'You sold an item',
        description: 'Paul Burgess just purchased “My - Admin Dashboard”!',
        timestamp: '16 hours ago',
        url: '',
    },
    {
        icon: 'ti ti-rocket',
        name: 'Product on the Theme Market',
        description: 'Reviewer added Admin Dashboard',
        timestamp: '22 hours ago',
        url: '',
    },
    {
        icon: 'ti ti-message',
        name: 'Robert Delaney',
        description: 'Send you message "Are you there?"',
        timestamp: '2 days ago',
        url: '',
    },
];
