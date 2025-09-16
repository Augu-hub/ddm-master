import { currency } from '@/helpers';
import type { ElectronicCardType, StatisticType, TransactionType, UserType } from '@/pages/dashboards/e-wallet/components/types';
import type { ApexChartType } from '@/types';

import discover from '@/images/cards/discover-white.svg';
import mastercard from '@/images/cards/mastercard.svg';
import payoneer from '@/images/cards/payoneer.svg';
import paypal from '@/images/cards/paypal.svg';
import stripe from '@/images/cards/stripe.svg';
import unionpay from '@/images/cards/unionpay.svg';
import visaLight from '@/images/cards/visa-white.svg';
import visa from '@/images/cards/visa.svg';

import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';

export const statistics: StatisticType[] = [
    {
        icon: 'solar:money-bag-bold-duotone',
        label: 'Total Income',
        value: 105.3,
        prefix: currency,
        suffix: 'k',
        growth: -0.93,
        url: '',
        chart: {
            type: 'area',
            height: 50,
            series: [
                {
                    data: [25, 28, 32, 38, 43, 55, 60, 48, 42, 51, 35],
                },
            ],
            options: {
                chart: {
                    type: 'area',
                    height: 50,
                    sparkline: {
                        enabled: true,
                    },
                },
                stroke: {
                    width: 2,
                    curve: 'smooth',
                },
                markers: {
                    size: 0,
                },
                colors: ['#6ac75a'],
                tooltip: {
                    fixed: {
                        enabled: false,
                    },
                    x: {
                        show: false,
                    },
                    y: {
                        title: {
                            formatter: function () {
                                return '';
                            },
                        },
                    },
                    marker: {
                        show: false,
                    },
                },
                fill: {
                    opacity: [1],
                    type: ['gradient'],
                    gradient: {
                        type: 'vertical',
                        //   shadeIntensity: 1,
                        inverseColors: false,
                        opacityFrom: 0.5,
                        opacityTo: 0,
                        stops: [0, 100],
                    },
                },
            },
        },
    },
    {
        icon: 'solar:hand-money-bold-duotone',
        label: 'Total Expense',
        value: 78.32,
        prefix: currency,
        suffix: 'k',
        growth: 8.72,
        url: '',
        chart: {
            type: 'bar',
            height: 60,
            series: [
                {
                    data: [47, 45, 74, 14, 56, 74, 14, 11, 7, 39, 82],
                },
            ],
            options: {
                chart: {
                    type: 'bar',
                    height: 60,
                    sparkline: {
                        enabled: true,
                    },
                },
                plotOptions: {
                    bar: {
                        horizontal: false,
                        columnWidth: '60%',
                        borderRadius: 4,
                    },
                },
                colors: ['#465dff'],
                labels: ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11'],
                xaxis: {
                    crosshairs: {
                        width: 1,
                    },
                },
                tooltip: {
                    fixed: {
                        enabled: false,
                    },
                    x: {
                        show: false,
                    },
                    y: {
                        title: {
                            formatter: function () {
                                return '';
                            },
                        },
                    },
                    marker: {
                        show: false,
                    },
                },
            },
        },
    },
];

export const balanceOverviewChart: ApexChartType = {
    height: 365,
    type: 'line',
    series: [
        {
            name: 'Total Income',
            type: 'bar',
            data: [89.25, 98.58, 68.74, 108.87, 77.54, 84.03, 51.24, 28.57, 92.57, 42.36, 88.51, 36.57],
        },
        {
            name: 'Total Expense',
            type: 'area',
            data: [34, 65, 46, 68, 49, 61, 42, 44, 78, 52, 63, 67],
        },
        {
            name: 'Investment',
            type: 'bar',
            data: [8, 12, 7, 17, 21, 11, 5, 9, 7, 29, 12, 35],
        },
    ],
    options: {
        chart: {
            height: 365,
            type: 'line',
            toolbar: {
                show: false,
            },
        },
        stroke: {
            dashArray: [0, 6, 0],
            width: [0, 2, 0],
            curve: 'smooth',
        },
        fill: {
            opacity: [1, 0.1, 1],
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
            min: 0,
            labels: {
                formatter: function (val: number) {
                    return val + 'k';
                },
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
                right: -2,
                bottom: 15,
                left: 10,
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
                columnWidth: '30%',
                barHeight: '70%',
                borderRadius: 3,
            },
        },
        colors: ['#465dff', '#6ac75a', '#f9c45c'],
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

export const transactionsTable = {
    header: ['ID', 'Name / Business', 'Description', 'Amount', 'Timestamp', 'Type', 'Payment Method', 'Status', '•••'],
    body: [
        {
            id: 'DK801',
            user: { name: 'Adam M' },
            description: 'Licensing Revenue',
            amount: 750,
            timestamp: { date: '20 Apr,24', time: '10:31:23 am' },
            type: 'credit',
            paymentMethod: {
                image: mastercard,
                lastDigits: 3954,
            },
            status: 'success',
        },
        {
            id: 'DK800',
            user: { name: 'Alexa Newsome', image: avatar2 },
            description: 'Invoice #1908',
            amount: -90.99,
            timestamp: { date: '18 Apr,24', time: '06:22:09 pm' },
            type: 'debit',
            paymentMethod: {
                image: visa,
                lastDigits: 9003,
            },
            status: 'success',
        },
        {
            id: 'DK799',
            user: { name: 'Payoneer', image: payoneer },
            description: 'Client Payment',
            amount: 190,
            timestamp: { date: '17 Apr,24', time: '09:17:05 am' },
            type: 'credit',
            paymentMethod: {
                image: mastercard,
                lastDigits: 3954,
            },
            status: 'success',
        },
        {
            id: 'DK798',
            user: { name: 'Payoneer', image: payoneer },
            description: 'Client Payment',
            amount: 190,
            timestamp: { date: '17 Apr,24', time: '09:15:25 am' },
            type: 'other',
            paymentMethod: {
                image: unionpay,
                lastDigits: 8751,
            },
            status: 'failed',
        },
        {
            id: 'DK797',
            user: { name: 'Shelly Dorey' },
            description: 'Custom Software Development',
            amount: 500,
            timestamp: { date: '16 Apr,24', time: '05:09:58 pm' },
            type: 'credit',
            paymentMethod: {
                image: paypal,
                name: 'PayPal',
            },
            status: 'success',
        },
        {
            id: 'DK796',
            user: { name: 'Fredrick Arnett', image: avatar5 },
            description: 'Envato Payout - Collaboration',
            amount: 1250,
            timestamp: { date: '16 Apr,24', time: '10:21:25 am' },
            type: 'other',
            paymentMethod: {
                image: stripe,
                name: 'Stripe',
            },
            status: 'on-hold',
        },
        {
            id: 'DK795',
            user: { name: 'Barbara Frink', image: avatar4 },
            description: 'Personal Payment',
            amount: -90,
            timestamp: { date: '12 Apr,24', time: '06:22:09 pm' },
            type: 'debit',
            paymentMethod: {
                image: visa,
                lastDigits: 9003,
            },
            status: 'success',
        },
    ] as TransactionType[],
};

export const quickTransfer: UserType[] = [
    {
        image: avatar4,
        name: 'Alexa Newsome',
    },
    {
        image: avatar5,
        name: 'Shelly Dorey',
    },
    {
        image: avatar3,
        name: 'Fredrick Arnett',
    },
    {
        image: avatar8,
        name: 'Barbara Frink',
    },
    {
        image: avatar2,
        name: 'Adam M',
    },
];

export const electronicCards: ElectronicCardType[] = [
    {
        owner: {
            name: 'Mr. Dhanoo K',
        },
        lastDigits: 1001,
        expiryDate: '10/32',
        provider: {
            image: visaLight,
        },
        balance: 38562.25,
        variant: 'primary',
        url: '',
    },
    {
        owner: {
            name: 'Mr. Dhanoo K',
        },
        lastDigits: 1001,
        expiryDate: '10/32',
        provider: {
            image: discover,
        },
        balance: 38562.25,
        variant: 'success',
        url: '',
    },
    {
        owner: {
            name: 'Mr. Dhanoo K',
        },
        lastDigits: 1001,
        expiryDate: '10/32',
        provider: {
            image: visaLight,
        },
        balance: 38562.25,
        variant: 'secondary',
        url: '',
    },
];
