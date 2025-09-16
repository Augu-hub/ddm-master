import type { ChatMessageType, ContactType } from '@/pages/apps/chat/components/types';

import avatar1 from '@/images/users/avatar-1.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

export const contacts: ContactType[] = [
    {
        image: avatar2,
        name: 'Brandon Smith',
        timestamp: '5:45am',
        unreadMessages: 3,
        lastMessage: 'How are you today?',
        isPinned: true,
        url: '',
    },
    {
        image: avatar5,
        name: 'James Zavel',
        timestamp: '4:30am',
        isTyping: true,
        seen: true,
        isPinned: true,
        url: '',
    },
    {
        image: avatar8,
        name: 'Maria Lopez',
        timestamp: '6:12pm',
        unreadMessages: 1,
        lastMessage: 'How are you today?',
        isPinned: true,
        url: '',
    },
    {
        name: 'Osen Discussion',
        timestamp: '6:12pm',
        lastMessage: "JS Developer's Come in office?",
        isPinned: true,
        url: '',
    },
    {
        image: avatar3,
        name: 'Brandon Smith',
        timestamp: '3:40am',
        lastMessage: 'Please check these design assets',
        url: '',
    },
    {
        icon: 'ti ti-brand-javascript',
        name: 'Javascript Team',
        timestamp: '3:30am',
        lastMessage: 'New Project?',
        url: '',
    },
    {
        icon: 'ti ti-brand-figma',
        name: 'UI Team',
        timestamp: '3:30am',
        lastMessage: 'Project Completed',
        sent: true,
        url: '',
    },
    {
        image: avatar4,
        name: 'Hoyt Bahe',
        timestamp: '2:33am',
        lastMessage: 'Hi there, How are you?',
        seen: true,
        url: '',
    },
    {
        image: avatar9,
        name: 'John Otta',
        timestamp: '4:35am',
        lastMessage: 'What next plan ?',
        seen: true,
        url: '',
    },
    {
        image: avatar6,
        name: 'Louis Moller',
        timestamp: 'Tue',
        lastMessage: 'Are you free for 15 min?',
        unreadMessages: 1,
        url: '',
    },
    {
        image: avatar7,
        name: 'David Callan',
        timestamp: 'Tue',
        lastMessage: 'Are you interested in learning?',
        unreadMessages: 3,
        url: '',
    },
    {
        image: avatar9,
        name: 'Sean Lee',
        timestamp: 'Fri',
        lastMessage: 'Howdy?',
        sent: true,
        url: '',
    },
    {
        icon: 'ti ti-brand-react',
        name: 'React Team',
        timestamp: 'Sat',
        lastMessage: '@jamesZavel Is new React employee',
        seen: true,
        url: '',
    },
];

export const messages: ChatMessageType[] = [
    {
        sender: {
            name: 'James.',
            image: avatar5,
        },
        timestamp: '10:04pm',
        messages: ['Hello! 👋'],
    },
    {
        recipient: {
            name: 'You.',
            image: avatar1,
        },
        timestamp: '10:05pm',
        messages: ['Hey there, how are you doing? Any plans for our upcoming meeting?'],
    },
    {
        sender: {
            name: 'James.',
            image: avatar5,
        },
        timestamp: '10:08pm',
        messages: ["Sure, everything's good."],
    },
    {
        recipient: {
            name: 'You.',
            image: avatar1,
        },
        timestamp: '10:10pm',
        messages: ['Fantastic! 👍'],
    },
    {
        sender: {
            name: 'James.',
            image: avatar5,
        },
        timestamp: '10:15pm',
        messages: ["If you're available, let's schedule it for today."],
    },
    {
        recipient: {
            name: 'You.',
            image: avatar1,
        },
        timestamp: '10:16pm',
        messages: ['Absolutely! Just give me a heads up if 2pm suits you.'],
    },
    {
        sender: {
            name: 'James.',
            image: avatar5,
        },
        timestamp: '10:18pm',
        messages: [
            "Apologies 😔, I've got another meeting at 2pm. Could we possibly shift it to 3pm?",
            'If you have a few extra minutes, we could also go over the presentation talk format.',
        ],
    },
    {
        recipient: {
            name: 'You.',
            image: avatar1,
        },
        timestamp: '10:19pm',
        messages: [
            "3pm works for me 👍. Absolutely, let's dive into the presentation format. It'd be fantastic to\n" +
                "      wrap that up today. I'm attaching last year's format and assets here for reference.",
        ],
    },
];
