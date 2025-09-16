import type { AppType, NotificationType } from '@/layouts/partials/types';
import type { MegaMenuItemType, MenuItemType } from '@/types/layout';

import deFlag from '@/images/flags/de.svg';
import esFlag from '@/images/flags/es.svg';
import inFlag from '@/images/flags/in.svg';
import itFlag from '@/images/flags/it.svg';
import ruFlag from '@/images/flags/ru.svg';
import usFlag from '@/images/flags/us.svg';

import aws from '@/images/brands/aws.svg';
import bitbucket from '@/images/brands/bitbucket.svg';
import bootstrap from '@/images/brands/bootstrap.svg';
import digitalOcean from '@/images/brands/digital-ocean.svg';
import dribbble from '@/images/brands/dribbble.svg';
import dropbox from '@/images/brands/dropbox.svg';
import gitlab from '@/images/brands/gitlab.svg';
import googleCloud from '@/images/brands/google-cloud.svg';
import slack from '@/images/brands/slack.svg';

import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';

// topbar
export const megaMenuItems: MegaMenuItemType[] = [
    {
        title: 'UI Components',
        links: [
            {
                label: 'Widgets',
                url: '',
            },
            {
                label: 'Dragula',
                url: '',
            },
            {
                label: 'Dropdowns',
                url: '',
            },
            {
                label: 'Ratings',
                url: '',
            },
            {
                label: 'Sweet Alerts',
                url: '',
            },
            {
                label: 'Scrollbar',
                url: '',
            },
            {
                label: 'Range Slider',
                url: '',
            },
        ],
    },
    {
        title: 'Applications',
        links: [
            {
                label: 'eCommerce Pages',
                url: '',
            },
            {
                label: 'Hospital',
                url: '',
            },
            {
                label: 'Email',
                url: '',
            },
            {
                label: 'Calendar',
                url: '',
            },
            {
                label: 'Kanban Board',
                url: '',
            },
            {
                label: 'Invoice Management',
                url: '',
            },
            {
                label: 'Pricing',
                url: '',
            },
        ],
    },
    {
        title: 'Extra Pages',
        bgVariant: 'light',
        links: [
            {
                label: 'Left Sidebar with User',
                url: '',
            },
            {
                label: 'Menu Collapsed',
                url: '',
            },
            {
                label: 'Small Left Sidebar',
                url: '',
            },
            {
                label: 'New Header Style',
                url: '',
            },
            {
                label: 'My Account',
                url: '',
            },
            {
                label: 'Maintenance & Coming Soon',
                url: '',
            },
        ],
    },
];

export const languages: MenuItemType[] = [
    {
        image: usFlag,
        label: 'English',
    },
    {
        image: inFlag,
        label: 'Hindi',
    },
    {
        image: deFlag,
        label: 'German',
    },
    {
        image: itFlag,
        label: 'Italian',
    },
    {
        image: esFlag,
        label: 'Spanish',
    },
    {
        image: ruFlag,
        label: 'Russian',
    },
];

export const apps: AppType[] = [
    {
        image: slack,
        name: 'Slack',
    },
    {
        image: gitlab,
        name: 'Gitlab',
    },
    {
        image: dribbble,
        name: 'Dribbble',
    },
    {
        image: bitbucket,
        name: 'Bitbucket',
    },
    {
        image: dropbox,
        name: 'Dropbox',
    },
    {
        image: googleCloud,
        name: 'G Cloud',
    },
    {
        image: aws,
        name: 'AWS',
    },
    {
        image: digitalOcean,
        name: 'Server',
    },
    {
        image: bootstrap,
        name: 'Bootstrap',
    },
];

export const notifications: NotificationType[] = [
    {
        sender: {
            image: avatar2,
            name: 'Glady Haid',
        },
        message: 'Glady Haid commented on paces admin status.',
        timestamp: '25m ago',
        type: 'commented',
    },
    {
        sender: {
            image: avatar4,
            name: 'Tommy Berry',
        },
        message: 'Tommy Berry donated $100.00 for Carbon removal program',
        timestamp: '58m ago',
        type: 'donated',
    },
    {
        message: 'You withdraw a $500 by New York ATM',
        timestamp: '2h ago',
        type: 'other',
    },
    {
        sender: {
            image: avatar7,
            name: 'Richard Allen',
        },
        message: 'Richard Allen followed you in Facebook',
        timestamp: '3h ago',
        type: 'followed',
    },
    {
        sender: {
            image: avatar10,
            name: 'Victor Collier',
        },
        message: 'Victor Collier liked you recent photo in Instagram',
        timestamp: '10h ago',
        type: 'liked',
    },
];

export const profileMenuItems: MenuItemType[] = [
    {
        label: 'My Account',
        icon: 'ti ti-user-hexagon',
    },
    {
        label: 'Wallet',
        icon: 'ti ti-wallet',
    },
    {
        label: 'Settings',
        icon: 'ti ti-settings',
    },
    {
        label: 'Support',
        icon: 'ti ti-lifebuoy',
    },
    {
        label: 'Lock Screen',
        icon: 'ti ti-lock-square-rounded',
    },
    {
        label: 'Sign Out',
        icon: 'ti ti-logout',
    },
];

// footer
export const footerItems: MenuItemType[] = [
    {
        label: 'About',
    },
    {
        label: 'Support',
    },
    {
        label: 'Contact Us',
    },
];
