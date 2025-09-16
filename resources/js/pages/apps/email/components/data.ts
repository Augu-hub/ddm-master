import amazon from '@/images/brands/amazon.svg';
import digitalOcean from '@/images/brands/digital-ocean.svg';
import dribbble from '@/images/brands/dribbble.svg';
import gitlab from '@/images/brands/gitlab.svg';
import instagram from '@/images/brands/instagram.svg';
import linkedIn from '@/images/brands/linkedin.svg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';
import type { EmailType } from '@/pages/apps/email/components/types';

export const emails: EmailType[] = [
    {
        sender: {
            image: avatar2,
            name: 'George Thomas',
        },
        subject: 'Request For Information',
        message: 'I hope you are doing well. I have a small request. Can you please...',
        receivedAt: 'Jan 5, 3:45 PM',
        label: 'client',
    },
    {
        isStared: true,
        sender: {
            image: avatar3,
            name: 'Robert C. Lane',
        },
        subject: 'Invitation For Meeting',
        message: 'Good Morning, I hope this email finds you well. I am writing to extra...',
        receivedAt: 'Mar 23, 7:30 AM',
        attachmentCount: 2,
        label: 'personal',
    },
    {
        sender: {
            image: dribbble,
            name: 'Dribbble',
        },
        subject: 'Become a successful self-taught designer',
        message: "There's no one right way to learn design. In fa...",
        receivedAt: 'Apr 10, 1:15 AM',
        label: 'marketing',
    },
    {
        isStared: true,
        sender: {
            image: avatar5,
            name: 'Darren C. Gallimore',
        },
        subject: 'Holiday Notice',
        message: 'Good Evening, I hope you are doing well. I have a small request.',
        receivedAt: 'May 8, 9:45 PM',
    },
    {
        sender: {
            image: avatar9,
            name: 'Mike A. Bell',
        },
        subject: 'Offer Letter',
        message: 'Thank you for applying. I hope you are doing well. I have a small.',
        receivedAt: 'Jun 16, 6:00 AM',
        label: 'office',
    },
    {
        isStared: true,
        sender: {
            image: avatar6,
            name: 'Bennett C. Rice',
        },
        subject: 'Apology Letter',
        message: 'I hope you are doing well. I have a small request. Can you please',
        receivedAt: 'Jun 16, 6:00 AM',
        attachmentCount: 4,
    },
    {
        sender: {
            image: gitlab,
            name: 'John J. Bowser',
        },
        subject: 'How to get started on Gitlab',
        message: 'We know setting off on a freelancing journey can feel intim...',
        receivedAt: 'Aug 22, 2:35 AM',
        attachmentCount: 3,
    },
    {
        sender: {
            image: avatar8,
            name: 'Jill N. Neal',
        },
        subject: 'Apply For Executive Position',
        message: 'I am writing to express my keen interest in the Executive Po...',
        receivedAt: 'Aug 22, 2:35 AM',
        label: 'personal',
    },
    {
        sender: {
            image: instagram,
            name: 'Instagram',
        },
        subject: 'You have received 2 new followers',
        message: '2 new followers, 1 new collected project, and more at...',
        receivedAt: 'Oct 31, 8:00 AM',
        label: 'marketing',
    },
    {
        sender: {
            image: amazon,
            name: 'Amazon',
        },
        subject: 'Your order is shipped',
        message: 'Your order is on the way with tracking...',
        receivedAt: 'Nov 19, 10:10 PM',
        label: 'personal',
        attachmentCount: 1,
    },
    {
        isStared: true,
        sender: {
            image: avatar7,
            name: 'Alfredo D. Rico',
        },
        subject: 'Class schedule',
        message: 'Your online class will be held on Saturday at 2:30 pm Bangladesh.',
        receivedAt: 'Dec 25, 12:30 PM',
        label: 'office',
    },
    {
        sender: {
            image: avatar4,
            name: 'Vernon B. Rutter',
        },
        subject: 'Invitation to attend our Exclusive Webinar',
        message: 'An exclusive webinar will be held on 23 January...',
        receivedAt: 'Jan 30, 4:50 AM',
        label: 'office',
    },
    {
        isStared: true,
        sender: {
            image: digitalOcean,
            name: 'Digital Ocean',
        },
        subject: "Update to Discord's Policies",
        message: 'Hey! we wanted to let you know that we are updating our Te...',
        receivedAt: 'Feb 9, 9:05 PM',
        label: 'client',
    },
    {
        isStared: true,
        sender: {
            image: linkedIn,
            name: 'Linkedin',
        },
        subject: 'New job similar to UI/UX',
        message: 'Jobs similar to UI/UX Designer at St Trinity Property group and s...',
        receivedAt: 'May 17, 3:45 PM',
        label: 'personal',
        attachmentCount: 4,
    },
];
