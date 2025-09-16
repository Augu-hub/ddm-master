import { MenuType } from '@/types/layout';

export const menu: MenuType[] = [
    {
        label: 'Dashboards',
        key: 'dashboards',
        isTitle: true,
        icon: 'ti ti-dashboard',
        children: [
            {
                url: '/',
                label: 'Sales',
                icon: 'ti ti-dashboard',
                parentKey: 'dashboards',
                badge: {
                    variant: 'success',
                    text: '5',
                },
            },
            {
                url: '/dashboards/clinic',
                label: 'Clinic',
                icon: 'ti ti-building-hospital',
                parentKey: 'dashboards',
            },
            {
                url: '/dashboards/e-wallet',
                label: 'eWallet',
                icon: 'ti ti-wallet',
                parentKey: 'dashboards',
                tooltip: {
                    variant: 'danger',
                    icon: 'ti ti-info-triangle',
                    text: 'Your wallet balance is <b>low!</b>',
                },
            },
        ],
    },
    {
        label: 'Apps',
        key: 'apps',
        isTitle: true,
        icon: 'ti ti-apps',
        children: [
            {
                url: '/apps/chat',
                label: 'Chat',
                icon: 'ti ti-message',
                parentKey: 'apps',
            },
            {
                url: '/apps/calendar',
                label: 'Calendar',
                icon: 'ti ti-calendar',
                parentKey: 'apps',
            },
            {
                url: '/apps/email',
                label: 'Email',
                icon: 'ti ti-inbox',
                parentKey: 'apps',
            },
            {
                url: '/apps/file-manager',
                label: 'File Manager',
                icon: 'ti ti-folder',
                parentKey: 'apps',
            },
            {
                label: 'Hospital',
                icon: 'ti ti-medical-cross',
                key: 'hospital',
                children: [
                    { url: '/hospital/doctors', label: 'Doctors', parentKey: 'hospital' },
                    { url: '/hospital/doctors/details', label: 'Doctors Details', parentKey: 'hospital' },
                    { url: '/hospital/doctors/add', label: 'Add Doctors', parentKey: 'hospital' },
                    { url: '/hospital/patients', label: 'Patients', parentKey: 'hospital' },
                    { url: '/hospital/patients/details', label: 'Patients Details', parentKey: 'hospital' },
                    { url: '/hospital/patients/add', label: 'Add Patients', parentKey: 'hospital' },
                    { url: '/hospital/appointments', label: 'Appointments', parentKey: 'hospital' },
                    { url: '/hospital/payments', label: 'Payments', parentKey: 'hospital' },
                    { url: '/hospital/departments', label: 'Departments', parentKey: 'hospital' },
                    { url: '/hospital/reviews', label: 'Reviews', parentKey: 'hospital' },
                    { url: '/hospital/contacts', label: 'Hospital Contacts', parentKey: 'hospital' },
                    { url: '/hospital/staffs', label: 'Staffs', parentKey: 'hospital' },
                ],
            },
            {
                label: 'Ecommerce',
                icon: 'ti ti-basket',
                key: 'ecommerce',
                children: [
                    { url: '/ecommerce/products', label: 'Products', parentKey: 'ecommerce' },
                    { url: '/ecommerce/products-grid', label: 'Products Grid', parentKey: 'ecommerce' },
                    { url: '/ecommerce/products/details', label: 'Product Details', parentKey: 'ecommerce' },
                    { url: '/ecommerce/products/add', label: 'Add Product', parentKey: 'ecommerce' },
                    { url: '/ecommerce/categories', label: 'Categories', parentKey: 'ecommerce' },
                    { url: '/ecommerce/orders', label: 'Orders', parentKey: 'ecommerce' },
                    { url: '/ecommerce/orders/details', label: 'Orders Details', parentKey: 'ecommerce' },
                    { url: '/ecommerce/customers', label: 'Customers', parentKey: 'ecommerce' },
                    { url: '/ecommerce/sellers', label: 'Sellers', parentKey: 'ecommerce' },
                ],
            },
            {
                label: 'Invoice',
                icon: 'ti ti-file-invoice',
                key: 'invoice',
                children: [
                    { url: '/apps/invoices', label: 'Invoices', parentKey: 'invoice' },
                    { url: '/apps/invoices/details', label: 'Invoice Details', parentKey: 'invoice' },
                    { url: '/apps/invoices/add', label: 'Add Invoice', parentKey: 'invoice' },
                ],
            },
        ],
    },
    {
        label: 'Pages',
        key: 'pages',
        isTitle: true,
        icon: 'ti ti-file-description',
        children: [
            {
                key: 'pages',
                label: 'Pages',
                icon: 'ti ti-files',
                children: [
                    {
                        url: '/pages/starter',
                        label: 'Starter Page',
                        parentKey: 'pages',
                    },
                    {
                        url: '/pages/faqs',
                        label: 'FAQs',
                        parentKey: 'pages',
                    },
                    {
                        url: '/pages/maintenance',
                        label: 'Maintenance',
                        parentKey: 'pages',
                    },
                    {
                        url: '/pages/timeline',
                        label: 'Timeline',
                        parentKey: 'pages',
                    },
                    {
                        url: '/pages/coming-soon',
                        label: 'Coming Soon',
                        parentKey: 'pages',
                    },
                    {
                        url: '/pages/terms-conditions',
                        label: 'Terms & Conditions',
                        parentKey: 'pages',
                    },
                ],
            },
            {
                key: 'pricing',
                label: 'Pricing',
                icon: 'ti ti-receipt-2',
                children: [
                    {
                        url: '/pages/pricing-1',
                        label: 'Pricing One',
                        parentKey: 'pricing',
                    },
                    {
                        url: '/pages/pricing-2',
                        label: 'Pricing Two',
                        parentKey: 'pricing',
                    },
                ],
            },
            {
                key: 'auth',
                label: 'Auth Pages',
                icon: 'ti ti-lock',
                children: [
                    {
                        url: '/auth/login',
                        label: 'Login',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/register',
                        label: 'Register',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/logout',
                        label: 'Logout',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/forgot-password',
                        label: 'Forgot Password',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/reset-password',
                        label: 'Reset Password',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/verify-email',
                        label: 'Verify Email',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/confirm-password',
                        label: 'Create Password',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/lock-screen',
                        label: 'Lock Screen',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/confirm-mail',
                        label: 'Confirm Mail',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/login-pin',
                        label: 'Login with PIN',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/2fa',
                        label: '2FA',
                        parentKey: 'auth',
                    },
                    {
                        url: '/auth/account-deactivation',
                        label: 'Account Deactivation',
                        parentKey: 'auth',
                    },
                ],
            },
            {
                key: 'error',
                label: 'Error Pages',
                icon: 'ti ti-server-2',
                children: [
                    {
                        url: '/error/400',
                        label: '400 Bad Request',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/401',
                        label: '401 Unauthorized',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/403',
                        label: '403 Forbidden',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/404',
                        label: '404 Not Found',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/408',
                        label: '408 Request Timeout',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/500',
                        label: '500 Internal Server',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/501',
                        label: '501 Not Implemented',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/502',
                        label: '502 Service Overloaded',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/503',
                        label: '503 Service Unavailable',
                        parentKey: 'error',
                    },
                    {
                        url: '/error/404-alt',
                        label: 'Error 404 Alt',
                        parentKey: 'error',
                    },
                ],
            },
            {
                key: 'email',
                label: 'Email Templates',
                icon: 'ti ti-news',
                children: [
                    {
                        url: '/pages/email-templates/basic',
                        label: 'Basic Email',
                        parentKey: 'email',
                        target: '_blank',
                    },
                    {
                        url: '/pages/email-templates/invoice',
                        label: 'Purchase Invoice',
                        parentKey: 'email',
                        target: '_blank',
                    },
                    {
                        url: '/pages/email-templates/activation',
                        label: 'Account Activation',
                        parentKey: 'email',
                        target: '_blank',
                    },
                ],
            },
        ],
    },
    {
        label: 'Components',
        key: 'components',
        isTitle: true,
        icon: 'ti ti-components',
        children: [
            {
                key: 'base-ui',
                label: 'Base UI',
                icon: 'ti ti-brightness',
                children: [
                    {
                        url: '/ui/accordions',
                        label: 'Accordions',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/alerts',
                        label: 'Alert',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/avatars',
                        label: 'Avatars',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/badges',
                        label: 'Badges',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/breadcrumb',
                        label: 'Breadcrumb',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/buttons',
                        label: 'Buttons',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/cards',
                        label: 'Cards',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/carousel',
                        label: 'Carousel',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/collapse',
                        label: 'Collapse',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/dropdowns',
                        label: 'Dropdowns',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/ratios',
                        label: 'Ratios',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/grid',
                        label: 'Grid',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/links',
                        label: 'Links',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/list-group',
                        label: 'List Group',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/modals',
                        label: 'Modals',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/notifications',
                        label: 'Notifications',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/offcanvas',
                        label: 'Offcanvas',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/placeholders',
                        label: 'Placeholders',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/pagination',
                        label: 'Pagination',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/popovers',
                        label: 'Popovers',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/progress',
                        label: 'Progress',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/scrollspy',
                        label: 'Scrollspy',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/spinners',
                        label: 'Spinners',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/tabs',
                        label: 'Tabs',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/tooltips',
                        label: 'Tooltips',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/typography',
                        label: 'Typography',
                        parentKey: 'base-ui',
                    },
                    {
                        url: '/ui/utilities',
                        label: 'Utilities',
                        parentKey: 'base-ui',
                    },
                ],
            },
            {
                key: 'extended-ui',
                label: 'Extended UI',
                icon: 'ti ti-alien',
                children: [
                    {
                        url: '/extended/dragula',
                        label: 'Dragula',
                        parentKey: 'extended-ui',
                    },
                    {
                        url: '/extended/sweetalerts',
                        label: 'Sweet Alerts',
                        parentKey: 'extended-ui',
                    },
                    {
                        url: '/extended/ratings',
                        label: 'Ratings',
                        parentKey: 'extended-ui',
                    },
                    {
                        url: '/extended/scrollbar',
                        label: 'Scrollbar',
                        parentKey: 'extended-ui',
                    },
                ],
            },
            {
                key: 'icons',
                label: 'Icons',
                icon: 'ti ti-leaf',
                children: [
                    {
                        url: '/icons/tabler',
                        label: 'Tabler',
                        parentKey: 'icons',
                    },
                    {
                        url: '/icons/solar',
                        label: 'Solar',
                        parentKey: 'icons',
                    },
                ],
            },
            {
                key: 'charts',
                label: 'Charts',
                icon: 'ti ti-chart-arcs',
                children: [
                    {
                        url: '/charts/area',
                        label: 'Area',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/bar',
                        label: 'Bar',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/bubble',
                        label: 'Bubble',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/candlestick',
                        label: 'Candlestick',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/column',
                        label: 'Column',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/heatmap',
                        label: 'Heatmap',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/line',
                        label: 'Line',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/mixed',
                        label: 'Mixed',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/timeline',
                        label: 'Timeline',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/boxplot',
                        label: 'Boxplot',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/treemap',
                        label: 'Treemap',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/pie',
                        label: 'Pie',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/radar',
                        label: 'Radar',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/radialbar',
                        label: 'RadialBar',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/scatter',
                        label: 'Scatter',
                        parentKey: 'charts',
                    },
                    {
                        url: '/charts/polar',
                        label: 'Polar Area',
                        parentKey: 'charts',
                    },
                ],
            },
            {
                key: 'forms',
                label: 'Forms',
                icon: 'ti ti-forms',
                children: [
                    {
                        url: '/forms/basic',
                        label: 'Basic Elements',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/inputmask',
                        label: 'Inputmask',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/picker',
                        label: 'Picker',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/select',
                        label: 'Select',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/slider',
                        label: 'Range Slider',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/validation',
                        label: 'Validation',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/wizard',
                        label: 'Wizard',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/file-uploads',
                        label: 'File Uploads',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/editors',
                        label: 'Editors',
                        parentKey: 'forms',
                    },
                    {
                        url: '/forms/layouts',
                        label: 'Layouts',
                        parentKey: 'forms',
                    },
                ],
            },
            {
                key: 'tables',
                label: 'Tables',
                icon: 'ti ti-table',
                children: [
                    {
                        url: '/tables/basic',
                        label: 'Basic Tables',
                        parentKey: 'tables',
                    },
                    {
                        url: '/tables/gridjs',
                        label: 'Gridjs Tables',
                        parentKey: 'tables',
                    },
                ],
            },
            {
                key: 'maps',
                label: 'Maps',
                icon: 'ti ti-map-pin',
                children: [
                    {
                        url: '/maps/google',
                        label: 'Google Maps',
                        parentKey: 'maps',
                    },
                    {
                        url: '/maps/vector',
                        label: 'Vector Maps',
                        parentKey: 'maps',
                    },
                    {
                        url: '/maps/leaflet',
                        label: 'Leaflet Maps',
                        parentKey: 'maps',
                    },
                ],
            },
        ],
    },
    {
        label: 'More',
        key: 'more',
        isTitle: true,
        icon: 'ti ti-dots',
        children: [
            {
                key: 'layouts',
                label: 'Layouts',
                icon: 'ti ti-layout',
                children: [
                    {
                        url: '/layouts/vertical',
                        label: 'Vertical',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/horizontal',
                        label: 'Horizontal',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/compact',
                        label: 'Compact',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/detached',
                        label: 'Detached',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/full-view',
                        label: 'Full',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/fullscreen-view',
                        label: 'Fullscreen',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/hover-menu',
                        label: 'Hover',
                        parentKey: 'layouts',
                    },
                    {
                        url: '/layouts/icon-view',
                        label: 'Icon View',
                        parentKey: 'layouts',
                    },
                ],
            },
        ],
    },
];
