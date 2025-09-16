import type { AppType, FileActivityType } from '@/pages/apps/file-manager/components/types';

import avatar1 from '@/images/users/avatar-1.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';

export const apps: AppType[] = [
    {
        icon: 'logos:google-meet',
        name: 'Google Media',
        totalFiles: 38,
        usedSpace: 44.6,
        totalSpace: 50,
        variant: 'primary',
        isStared: true,
    },
    {
        icon: 'logos:google-drive',
        name: 'Google Drive',
        totalFiles: 42,
        usedSpace: 34.8,
        totalSpace: 50,
        variant: 'success',
    },
    {
        icon: 'logos:dropbox',
        name: 'Dropbox',
        totalFiles: 98,
        usedSpace: 44.86,
        totalSpace: 50,
        variant: 'info',
    },
    {
        icon: 'logos:cloudlinux',
        name: 'Cloud Storage',
        totalFiles: 56,
        usedSpace: 20.63,
        totalSpace: 50,
        variant: 'secondary',
        isStared: true,
    },
];

export const recentFileActivityTable = {
    header: ['Name', 'Uploaded By', 'Size', 'Last', 'Members', 'Action'],
    body: [
        {
            file: {
                icon: 'ti ti-file-type-docx',
                name: 'Dashboard-requirements.docx',
                totalFiles: 12,
                type: 'docx',
                variant: 'info',
            },
            uploadedBy: {
                image: avatar1,
                name: 'Harriett E. Penix',
                email: 'harriettepenix@rhyta.com',
            },
            size: '128 MB',
            lastUpdated: 'April 25, 2023',
            members: [
                {
                    variant: 'success',
                    name: 'D',
                },
                {
                    variant: 'primary',
                    name: 'K',
                },
                {
                    variant: 'secondary',
                    name: 'H',
                },
                {
                    variant: 'warning',
                    name: 'L',
                },
                {
                    variant: 'info',
                    name: 'G',
                },
            ],
        },
        {
            file: {
                icon: 'ti ti-file-type-pdf',
                name: 'ocen-dashboard.pdf',
                totalFiles: 18,
                type: 'pdf',
                variant: 'danger',
            },
            uploadedBy: {
                image: avatar2,
                name: 'Carol L. Simon',
                email: 'carollcimon@jourrapide.com',
            },
            size: '521 MB',
            lastUpdated: 'April 28, 2023',
            members: [
                {
                    variant: 'danger',
                    name: 'Y',
                },
                {
                    variant: 'success',
                    name: 'L',
                },
                {
                    variant: 'dark',
                    name: 'O',
                },
                {
                    variant: 'warning',
                    name: 'J',
                },
                {
                    variant: 'primary',
                    name: 'G',
                },
            ],
        },
        {
            file: {
                icon: 'ti ti-files',
                name: 'Dashboard tech requirements',
                totalFiles: 12,
                type: 'txt',
                variant: 'warning',
            },
            uploadedBy: {
                image: avatar3,
                name: 'Rosa L. Winters',
                email: 'rosalwinters@teleworm.us',
            },
            size: '7.2 MB',
            lastUpdated: 'May 1, 2023',
            members: [
                {
                    variant: 'primary',
                    name: 'A',
                },
                {
                    variant: 'warning',
                    name: 'B',
                },
                {
                    variant: 'danger',
                    name: 'R',
                },
                {
                    variant: 'secondary',
                    name: 'C',
                },
                {
                    variant: 'dark',
                    name: 'U',
                },
            ],
        },
        {
            file: {
                icon: 'ti ti-file-type-jpg',
                name: 'dashboard.jpg',
                totalFiles: 12,
                type: 'jpg',
                variant: 'primary',
            },
            uploadedBy: {
                image: avatar4,
                name: 'Jeremy C. Willi',
                email: 'jeremycwilliams@dayrep.com',
            },
            size: '54.2 MB',
            lastUpdated: 'May 2, 2023',
            members: [
                {
                    variant: 'warning',
                    name: 'L',
                },
                {
                    variant: 'secondary',
                    name: 'Y',
                },
                {
                    variant: 'dark',
                    name: 'A',
                },
                {
                    variant: 'primary',
                    name: 'R',
                },
                {
                    variant: 'info',
                    name: 'V',
                },
            ],
        },
        {
            file: {
                icon: 'ti ti-file-type-zip',
                name: 'admin-hospital.zip',
                variant: 'success',
            },
            uploadedBy: {
                image: avatar5,
                name: 'James R. Alvares',
                email: 'jamesralvares@jourrapide.com',
            },
            size: '8.3 MB',
            lastUpdated: 'May 6, 2023',
            members: [
                {
                    variant: 'dark',
                    name: 'G',
                },
                {
                    variant: 'light',
                    name: 'O',
                },
                {
                    variant: 'secondary',
                    name: 'W',
                },
                {
                    variant: 'primary',
                    name: 'A',
                },
                {
                    variant: 'warning',
                    name: 'K',
                },
            ],
        },
    ] as FileActivityType[],
};
