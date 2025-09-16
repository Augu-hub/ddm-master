export type StatisticType = {
    label: string;
    value: number;
    icon?: string;
    prefix?: string;
    suffix?: string;
    badge?: {
        text: string;
        variant?: string;
    };
    subStatistic?: StatisticType[];
    url?: string;
};

export type DoctorType = {
    name: string;
    image: string;
    specialistIn?: string;
    overallRating?: number;
    totalReviewCount?: number;
    url?: string;
};

export type AppointmentType = {
    queue: number;
    name: string;
    gender: 'male' | 'female' | 'other';
    age: number;
    appointmentFor: string;
    date: string;
    time: string;
    assignedDoctor: DoctorType;
    status: 'completed' | 'canceled' | 'scheduled';
};
