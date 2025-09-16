import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';

type ReviewType = {
    name: string;
    timestamp: string;
    rating: number;
    comment: string;
    likes: number;
    dislikes: number;
};

export type DoctorReviewType = {
    name: string;
    image: string;
    specialistIn: string;
    overallRating: number;
    totalReviews: number;
    reviews: ReviewType[];
};

export const doctorReviews: DoctorReviewType[] = [
    {
        image: avatar3,
        name: 'Dr. James Roger',
        specialistIn: 'Cardiology',
        overallRating: 4.9,
        totalReviews: 2300,
        reviews: [
            {
                name: 'Oliver Baynton',
                rating: 5,
                timestamp: '11 Jun 2024',
                comment:
                    "Dr. James Roger is exceptional. A great listener and communicator. Makes our family's health a top priority. Highly recommended",
                likes: 632,
                dislikes: 9,
            },
            {
                name: 'Jonathan Hort',
                rating: 5,
                timestamp: '03 May 2024',
                comment:
                    "Our family doctor, provides outstanding care. Always prompt, understanding, and offers excellent guidance. We couldn't ask for a better healthcare partner. Compassionate, thorough, and always available when needed. Our family's health is in capable hands.",
                likes: 212,
                dislikes: 4,
            },
        ],
    },
    {
        image: avatar2,
        name: 'Dr. Morgan Beck',
        specialistIn: 'Dermatology',
        overallRating: 4.7,
        totalReviews: 1600,
        reviews: [
            {
                name: 'Hugo Strele',
                rating: 5,
                timestamp: '15 March 2024',
                comment: "He genuinely cares about our family's health and goes above and beyond to ensure we receive the best care possible.",
                likes: 322,
                dislikes: 8,
            },
            {
                name: 'Mackenzie McHale',
                rating: 5,
                timestamp: '12 Jan 2024',
                comment:
                    "Dr. Morgan Beck is a true gem. Always attentive, patient, and knowledgeable. Takes time to explain things and makes us feel at ease during. provides outstanding care. Always prompt, nderstanding, and offers excellent guidance. We couldn't ask for a better healthcare partner.",
                likes: 532,
                dislikes: 43,
            },
        ],
    },
    {
        image: avatar4,
        name: 'Dr. Terry Bowers',
        specialistIn: 'Pediatrics',
        overallRating: 4.5,
        totalReviews: 2500,
        reviews: [
            {
                name: 'Lauren Oberg',
                rating: 5,
                timestamp: '20 Dec 2023',
                comment: 'Dr. Roger consistently goes the extra mile, making him our trusted choice for medical advice and treatment.',
                likes: 452,
                dislikes: 23,
            },
            {
                name: 'Ralph Kappel',
                rating: 4.5,
                timestamp: '17 Nov 2023',
                comment:
                    'Dr. Terry Bowers was exceptional. They took the time to listen attentively to our concerns and thoroughly explain everything in a way that was easy to understand. Their expertise and compassionate approach reassured us throughout the appointment.',
                likes: 621,
                dislikes: 60,
            },
        ],
    },
    {
        image: avatar5,
        name: 'Dr. Carlos McCollum',
        specialistIn: 'Orthopedics',
        overallRating: 4.1,
        totalReviews: 4200,
        reviews: [
            {
                name: 'Anja Bachmeier',
                rating: 4,
                timestamp: '20 Nov 2023',
                comment:
                    'I am incredibly grateful for the care I received at ortho care. Thanks to their expertise and dedication, I am now on the road to recovery.',
                likes: 841,
                dislikes: 20,
            },
            {
                name: 'Jan Fuhrmann',
                rating: 4.5,
                timestamp: '25 Oct 2023',
                comment:
                    'I am incredibly grateful for the care I received at [Orthopedics Practice Name]. Thanks to their expertise and dedication, I am now on the road to recovery. I highly recommend ortho care to anyone in need of orthopedic care.',
                likes: 732,
                dislikes: 120,
            },
        ],
    },
    {
        image: avatar8,
        name: 'Dr. Erma Coffman',
        specialistIn: 'Gastroenterology',
        overallRating: 4.3,
        totalReviews: 3710,
        reviews: [
            {
                name: 'Katja Theissen',
                rating: 4.5,
                timestamp: '14 July 2023',
                comment:
                    'The nursing staff and medical assistants were also fantastic. They were attentive, caring, and always willing to answer any questions I had.',
                likes: 287,
                dislikes: 0,
            },
            {
                name: 'Torsten Fisher',
                rating: 4.5,
                timestamp: '21 May 2023',
                comment:
                    'Dr. Erma Coffman is a true professional. They took the time to listen attentively to my symptoms, a comprehensive examination, and explained my diagnosis and treatment options clearly. Their xpertise and genuine concern for my well-being were evident throughout our consultation.',
                likes: 400,
                dislikes: 80,
            },
        ],
    },
    {
        image: avatar10,
        name: 'Dr. Kelli Bailey',
        specialistIn: 'Psychiatry',
        overallRating: 3.5,
        totalReviews: 892,
        reviews: [
            {
                name: 'Lukas Kastner',
                rating: 4,
                timestamp: '09 May 2023',
                comment:
                    'The entire atmosphere at Psycho is calming and conducive to healing. The support staff were always helpful, ensuring my visits were stress-free.',
                likes: 79,
                dislikes: 2,
            },
            {
                name: 'Martina Hofman',
                rating: 4.5,
                timestamp: '18 March 2023',
                comment:
                    'I have seen significant improvements in my mental health since starting treatment with Dr. Kelli Bailey . I would highly recommend Psycho to anyone seeking compassionate and effective psychiatry care. They were accommodating and made me feel comfortable right from the start.',
                likes: 128,
                dislikes: 23,
            },
        ],
    },
];
