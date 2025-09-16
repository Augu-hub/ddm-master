import seller1 from '@/images/sellers/s-1.svg';
import seller2 from '@/images/sellers/s-2.svg';
import seller4 from '@/images/sellers/s-4.svg';
import seller6 from '@/images/sellers/s-6.svg';
import seller7 from '@/images/sellers/s-7.svg';
import seller8 from '@/images/sellers/s-8.svg';
import type { ApexChartType } from '@/types';

export type SellerType = {
    name: string;
    image: string;
    rating: number;
    totalRating: number;
    about: string;
    email: string;
    address: string;
    inStockItems: number;
    sells: number;
    brand: string;
    revenue: number;
    revenueChart: ApexChartType;
};

export const sellers: SellerType[] = [
    {
        image: seller1,
        name: 'Lacoste',
        rating: 4.5,
        totalRating: 5300,
        about: 'Lacoste, a global icon in the world of fashion, was founded in 1933 by the legendary French tennis player René Lacoste.',
        address: '966 Hiddenview Drive Philadelphia,',
        email: 'lacostefashion@rhyta.com',
        inStockItems: 941,
        sells: 6702,
        brand: 'Fashion',
        revenue: 62100,
        revenueChart: {
            type: 'area',
            height: 100,
            series: [{ data: [25, 66, 41, 89, 63, 25, 44, 12, 36, 9, 54] }],
            options: {
                chart: { type: 'area', height: 100, sparkline: { enabled: !0 } },
                stroke: { width: 2, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
                markers: { size: 0 },
                colors: ['#465dff'],
                tooltip: {
                    fixed: { enabled: !1 },
                    x: { show: !1 },
                    y: {
                        title: {
                            formatter: function (e) {
                                return '';
                            },
                        },
                    },
                    marker: { show: !1 },
                },
            },
        },
    },
    {
        image: seller4,
        name: 'Asics Foot Ware',
        rating: 4.5,
        totalRating: 2500,
        about: 'Asics footwear is renowned for its advanced technology and superior craftsmanship, making it a favorite among athletes and fitness worldwide.',
        address: '2267 Raver Croft Drive Chattanooga,',
        email: 'asionwares@rhyta.com',
        inStockItems: 764,
        sells: 2941,
        brand: 'Footware',
        revenue: 40400,
        revenueChart: {
            type: 'area',
            height: 100,
            series: [{ data: [17, 83, 56, 45, 29, 92, 38, 72, 11, 67, 53] }],
            options: {
                chart: { type: 'area', height: 100, sparkline: { enabled: !0 } },
                stroke: { width: 2, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
                markers: { size: 0 },
                colors: ['#465dff'],
                tooltip: {
                    fixed: { enabled: !1 },
                    x: { show: !1 },
                    y: {
                        title: {
                            formatter: function (e) {
                                return '';
                            },
                        },
                    },
                    marker: { show: !1 },
                },
            },
        },
    },
    {
        image: seller6,
        name: 'American Tourister',
        rating: 4.5,
        totalRating: 4900,
        about: 'American Tourister, a trusted name in the luggage industry, has been synonymous with quality, durability, and style since its founding in 1933.',
        address: '3383 Briarhill Lane Youngstown,',
        email: 'americanbag@rhyta.com',
        inStockItems: 1600,
        sells: 5123,
        brand: 'Luggage',
        revenue: 75450,
        revenueChart: {
            type: 'area',
            height: 100,
            series: [{ data: [34, 77, 23, 65, 48, 90, 14, 69, 37, 52, 81] }],
            options: {
                chart: { type: 'area', height: 100, sparkline: { enabled: !0 } },
                stroke: { width: 2, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
                markers: { size: 0 },
                colors: ['#465dff'],
                tooltip: {
                    fixed: { enabled: !1 },
                    x: { show: !1 },
                    y: {
                        title: {
                            formatter: function (e) {
                                return '';
                            },
                        },
                    },
                    marker: { show: !1 },
                },
            },
        },
    },
    {
        image: seller7,
        name: 'Hitachi',
        rating: 4.5,
        totalRating: 8000,
        about: 'Hitachi, Ltd., founded in 1910, is a global leader in technology and innovation, renowned for its diverse range of products and services.',
        address: '2496 Gladwell Street Cleburne,',
        email: 'hitachielectronics@rhyta.com',
        inStockItems: 3100,
        sells: 1598,
        brand: 'Electronics',
        revenue: 98900,
        revenueChart: {
            type: 'area',
            height: 100,
            series: [{ data: [45, 12, 78, 31, 56, 89, 22, 67, 41, 53, 96] }],
            options: {
                chart: { type: 'area', height: 100, sparkline: { enabled: !0 } },
                stroke: { width: 2, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
                markers: { size: 0 },
                colors: ['#465dff'],
                tooltip: {
                    fixed: { enabled: !1 },
                    x: { show: !1 },
                    y: {
                        title: {
                            formatter: function (e) {
                                return '';
                            },
                        },
                    },
                    marker: { show: !1 },
                },
            },
        },
    },
    {
        image: seller8,
        name: 'Pepperfry',
        rating: 4.5,
        totalRating: 6900,
        about: "Pepperfry, launched in 2012, has rapidly grown to become India's leading online marketplace for furniture and home decor.",
        address: '3840 Sunset Drive Brinkley,',
        email: 'pepperfryfurniture@rhyta.com',
        inStockItems: 2900,
        sells: 7506,
        brand: 'Furniture',
        revenue: 54810,
        revenueChart: {
            type: 'area',
            height: 100,
            series: [{ data: [72, 35, 87, 23, 56, 94, 11, 68, 49, 75, 31] }],
            options: {
                chart: { type: 'area', height: 100, sparkline: { enabled: !0 } },
                stroke: { width: 2, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
                markers: { size: 0 },
                colors: ['#465dff'],
                tooltip: {
                    fixed: { enabled: !1 },
                    x: { show: !1 },
                    y: {
                        title: {
                            formatter: function (e) {
                                return '';
                            },
                        },
                    },
                    marker: { show: !1 },
                },
            },
        },
    },
    {
        image: seller2,
        name: 'Skulcandy',
        rating: 4.5,
        totalRating: 7500,
        about: 'Skullcandy, founded in 2003 by Rick Alden, is a leading audio brand known for its innovative and stylish audio accessories.',
        address: '1024 Veltri Drive Takotna,',
        email: 'skulcandyaudio@rhyta.com',
        inStockItems: 4800,
        sells: 1031,
        brand: 'Audio',
        revenue: 63219,
        revenueChart: {
            type: 'area',
            height: 100,
            series: [{ data: [18, 47, 32, 76, 51, 22, 65, 39, 58, 14, 83] }],
            options: {
                chart: { type: 'area', height: 100, sparkline: { enabled: !0 } },
                stroke: { width: 2, curve: 'smooth' },
                fill: {
                    type: 'gradient',
                    gradient: {
                        shade: 'light',
                        type: 'vertical',
                        opacityFrom: 0.4,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
                markers: { size: 0 },
                colors: ['#465dff'],
                tooltip: {
                    fixed: { enabled: !1 },
                    x: { show: !1 },
                    y: {
                        title: {
                            formatter: function (e) {
                                return '';
                            },
                        },
                    },
                    marker: { show: !1 },
                },
            },
        },
    },
];
