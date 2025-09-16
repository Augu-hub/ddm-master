export type StatisticType = {
    label: string;
    value: number;
    icon: string;
    prefix?: string;
    suffix?: string;
    duration?: 'week' | 'month' | 'year';
};

type PaymentType = {
    billNo: number;
    patient: string;
    doctor: string;
    insuranceCompany: string;
    payment: 'cashless' | 'cash';
    billDate: string;
    charge: number;
    tax: number;
    discount: number;
    total: number;
};

export const statistics: StatisticType[] = [
    {
        icon: 'solar:users-group-two-rounded-bold',
        label: 'Number of Patients',
        value: 3421,
        duration: 'month',
    },
    {
        icon: 'solar:bill-list-bold',
        label: 'Total Bill Payments',
        value: 2342,
    },
    {
        icon: 'solar:bill-check-bold',
        label: 'Total Paid Bills',
        value: 1310,
    },
    {
        icon: 'solar:bill-cross-bold',
        label: 'Total Unpaid Bills',
        value: 1203,
    },
];

export const paymentList: PaymentType[] = [
    {
        billNo: 1,
        patient: 'Keith Jacobson',
        doctor: 'Dr.Justin Williams',
        insuranceCompany: 'Tata MediCare Insurance',
        payment: 'cashless',
        billDate: '05/11/2023',
        charge: 1500,
        tax: 15,
        discount: 10,
        total: 1500,
    },
    {
        billNo: 2,
        patient: 'Fred Godina',
        doctor: 'Dr.Thomas Fant',
        insuranceCompany: 'Star Health insurance',
        payment: 'cashless',
        billDate: '18/03/2023',
        charge: 1500,
        tax: 10,
        discount: 10,
        total: 3500,
    },
    {
        billNo: 3,
        patient: 'Greg Crosby',
        doctor: 'Dr.Aretha Garland',
        insuranceCompany: 'Apollo Health Insurance',
        payment: 'cashless',
        billDate: '29/08/2023',
        charge: 1500,
        tax: 11,
        discount: 10,
        total: 5000,
    },
    {
        billNo: 4,
        patient: 'Jennifer Doss',
        doctor: 'Dr.Justin Williams',
        insuranceCompany: 'LIC Health Insurance',
        payment: 'cashless',
        billDate: '12/02/2023',
        charge: 1500,
        tax: 10,
        discount: 10,
        total: 1500,
    },
    {
        billNo: 5,
        patient: 'Peggy Doe',
        doctor: 'Dr.Thomas Fant',
        insuranceCompany: 'National Insurance',
        payment: 'cashless',
        billDate: '07/04/2023',
        charge: 1500,
        tax: 18,
        discount: 10,
        total: 3500,
    },
    {
        billNo: 6,
        patient: 'Donald Gardner',
        doctor: 'Dr.Aretha Garland',
        insuranceCompany: 'Star Health insurance',
        payment: 'cashless',
        billDate: '15/10/2023',
        charge: 1500,
        tax: 18,
        discount: 10,
        total: 5000,
    },
    {
        billNo: 7,
        patient: 'Anna Campbel',
        doctor: 'Dr.Joshua Ampt',
        insuranceCompany: 'LIC Health Insurance',
        payment: 'cashless',
        billDate: '23/05/2023',
        charge: 1500,
        tax: 10,
        discount: 10,
        total: 5000,
    },
    {
        billNo: 8,
        patient: 'Rachel Fox',
        doctor: 'Dr.Elijah Wylde',
        insuranceCompany: 'General Insurance Limited',
        payment: 'cashless',
        billDate: '30/06/2024',
        charge: 1322,
        tax: 18,
        discount: 10,
        total: 2300,
    },
    {
        billNo: 9,
        patient: 'Sebastian Barrow',
        doctor: 'Dr.Madeline Panton',
        insuranceCompany: 'Insurance Company Limited',
        payment: 'cashless',
        billDate: '09/09/2023',
        charge: 1500,
        tax: 10,
        discount: 10,
        total: 4800,
    },
    {
        billNo: 10,
        patient: 'Hugo Grey-Smith',
        doctor: 'Dr.Angus Rich',
        insuranceCompany: 'LIC Health Insurance',
        payment: 'cashless',
        billDate: '14/01/2023',
        charge: 2500,
        tax: 18,
        discount: 10,
        total: 4000,
    },
];
