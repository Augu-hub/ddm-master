import type { IconifyIcon } from '@iconify/vue';

export type MenuItemType = {
    label: string;
    icon?: string | IconifyIcon;
    image?: string;
    url?: string;
};

export type MegaMenuItemType = {
    title: string;
    bgVariant?: string;
    links: {
        label: string;
        url: string;
    }[];
};

export type MenuType = {
    key?: string;
    isTitle?: boolean;
    icon?: string;
    label: string;
    url?: string;
    parentKey?: string;
    badge?: {
        variant: string;
        text: string;
    };
    tooltip?: {
        variant: string;
        icon: string;
        text: string;
    };
    target?: '_blank' | '_top' | '_parent' | '_self';
    children?: MenuType[];
};
