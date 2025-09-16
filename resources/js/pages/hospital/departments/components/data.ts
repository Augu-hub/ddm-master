import department1 from '@/images/department/d-1.png';
import department2 from '@/images/department/d-2.png';
import department3 from '@/images/department/d-3.png';
import department4 from '@/images/department/d-4.png';
import department5 from '@/images/department/d-5.png';
import department6 from '@/images/department/d-6.png';
import department7 from '@/images/department/d-7.png';
import department8 from '@/images/department/d-8.png';

import avatar1 from '@/images/users/avatar-1.jpg';
import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

export type DepartmentType = {
    image: string;
    name: string;
    about: string;
    rating: number;
    totalReviews: number;
    doctors: {
        image?: string;
        name?: string;
    }[];
};

export const departments: DepartmentType[] = [
    {
        image: department1,
        name: 'Cardiology Department',
        about: 'Cardiologists diagnose and treat conditions such as congenital heart defects, coronary artery disease, heart failure, and valvular heart disease.',
        rating: 4.5,
        totalReviews: 4500,
        doctors: [
            {
                image: avatar10,
                name: 'Vicki',
            },
            {
                name: 'Thomas',
            },
            {
                image: avatar1,
                name: 'Chris',
            },
            {},
            {},
            {},
            {},
            {},
            {},
            {},
            {},
            {},
        ],
    },
    {
        image: department2,
        name: 'Pediatrics Department',
        about: 'Pediatricians are trained to diagnose and treat a wide range of childhood illnesses, from minor health problems to serious diseases.',
        rating: 4.5,
        totalReviews: 3100,
        doctors: [
            {
                image: avatar3,
                name: 'Vicki',
            },
            {
                image: avatar2,
                name: 'Vicki',
            },
            {
                image: avatar8,
                name: 'Chris',
            },
            {},
            {},
            {},
        ],
    },
    {
        image: department3,
        name: 'Orthopedic Department',
        about: 'Orthopedic surgeons specialize in surgeries involving bones, joints, ligaments, tendons, and muscles.',
        rating: 5,
        totalReviews: 1400,
        doctors: [
            {
                image: avatar4,
                name: 'Vicki',
            },
            {
                image: avatar7,
                name: 'Vicki',
            },
            {
                name: 'Sam',
            },
            {},
            {},
            {},
            {},
            {},
            {},
            {},
        ],
    },
    {
        image: department4,
        name: 'Oncology Department',
        about: 'Oncologists are medical professionals who manage the care of patients with cancer,radiation therapy, and surgical interventions.',
        rating: 4.5,
        totalReviews: 2500,
        doctors: [
            {
                image: avatar9,
                name: 'Vicki',
            },
            {
                image: avatar3,
                name: 'Vicki',
            },
            {
                name: 'Adam',
            },
            {},
            {},
        ],
    },
    {
        image: department5,
        name: 'Ophthalmologist Department',
        about: 'Ophthalmologists are medical doctors specializing in eye and vision care, including medical and surgical treatments.',
        rating: 4.5,
        totalReviews: 3100,
        doctors: [
            {
                name: 'Peter',
            },
            {
                name: 'Dany',
            },
            {
                name: 'Adam',
            },
            {},
            {},
        ],
    },
    {
        image: department6,
        name: 'Imaging Department',
        about: 'Common types of medical imaging include X-rays, computed tomography (CT) scans, magnetic resonance imaging (MRI), and ultrasound.',
        rating: 4.5,
        totalReviews: 2700,
        doctors: [
            {
                image: avatar10,
                name: 'Peter',
            },
            {
                image: avatar8,
                name: 'Dany',
            },
            {
                name: 'Musk',
            },
            {},
            {},
            {},
            {},
            {},
        ],
    },
    {
        image: department7,
        name: 'Gastroenterology Department',
        about: 'Gastroenterologists diagnose and treat conditions affecting the gastrointestinal tract, including the esophagus, stomach , liver, pancreas.',
        rating: 4.5,
        totalReviews: 1900,
        doctors: [
            {
                image: avatar3,
                name: 'Peter',
            },
            {
                image: avatar5,
                name: 'Dany',
            },
            {
                image: avatar8,
                name: 'Musk',
            },
            {},
            {},
        ],
    },
    {
        image: department8,
        name: 'Neurology Department',
        about: "Neurologists diagnose and treat conditions such as epilepsy, multiple sclerosis, Parkinson's disease, and stroke.",
        rating: 4.5,
        totalReviews: 2100,
        doctors: [
            {
                image: avatar2,
                name: 'Peter',
            },
            {
                name: 'Adam',
            },
            {
                image: avatar6,
                name: 'Musk',
            },
            {},
            {},
            {},
            {},
            {},
            {},
        ],
    },
];
