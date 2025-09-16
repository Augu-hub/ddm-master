<template>
    <header class="app-topbar">
        <div class="page-container topbar-menu">
            <div class="d-flex align-items-center gap-2">
                <LogoBox />

                <!-- Sidebar Menu Toggle Button -->
                <button class="sidenav-toggle-button px-2" @click="toggleLeftSideBar">
                    <i class="ti ti-menu-deep fs-24"></i>
                </button>

                <!-- Horizontal Menu Toggle Button -->
                <button class="topnav-toggle-button px-2" v-b-toggle.navbar>
                    <i class="ti ti-menu-deep fs-22"></i>
                </button>

                <!-- Button Trigger Search Modal -->
                <div class="topbar-search text-muted d-none d-xl-flex align-items-center gap-2" type="button" @click="toggleShowSearchModal">
                    <i class="ti ti-search fs-18"></i>
                    <span class="me-2">Search something..</span>
                    <span class="fw-medium ms-auto">⌘K</span>
                </div>

                <!-- Mega Menu -->
                <MegaMenu />
            </div>

            <div class="d-flex align-items-center gap-2">
                <!-- Search for small devices -->
                <div class="topbar-item d-flex d-xl-none">
                    <button class="topbar-link" @click="toggleShowSearchModal" type="button">
                        <i class="ti ti-search fs-22"></i>
                    </button>
                </div>

                <!-- Language Dropdown -->
                <div class="topbar-item">
                    <DropDown>
                        <button
                            class="topbar-link"
                            data-bs-toggle="dropdown"
                            data-bs-offset="0,25"
                            type="button"
                            aria-haspopup="false"
                            aria-expanded="false"
                        >
                            <img :src="usFlag" alt="user-image" class="w-100 rounded" height="18" id="selected-language-image" />
                        </button>

                        <div class="dropdown-menu dropdown-menu-end">
                            <Link v-for="item in languages" :key="item.label" href="" class="dropdown-item" role="button">
                                <img :src="item.image" alt="user-image" class="me-1 rounded" height="18" />
                                <span class="align-middle">{{ item.label }}</span>
                            </Link>
                        </div>
                    </DropDown>
                </div>

                <!-- Notification Dropdown -->
                <div class="topbar-item">
                    <DropDown>
                        <button
                            class="topbar-link dropdown-toggle drop-arrow-none"
                            data-bs-toggle="dropdown"
                            data-bs-offset="0,25"
                            type="button"
                            data-bs-auto-close="outside"
                            aria-haspopup="false"
                            aria-expanded="false"
                        >
                            <i class="ti ti-bell animate-ring fs-22"></i>
                            <span class="noti-icon-badge"></span>
                        </button>

                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg p-0" style="min-height: 300px">
                            <div class="border-bottom border-dashed p-3">
                                <b-row class="align-items-center">
                                    <b-col>
                                        <h6 class="fs-16 fw-semibold m-0">Notifications</h6>
                                    </b-col>
                                    <div class="col-auto">
                                        <div class="dropdown">
                                            <a
                                                href="#"
                                                class="dropdown-toggle drop-arrow-none link-dark"
                                                data-bs-toggle="dropdown"
                                                data-bs-offset="0,15"
                                                aria-expanded="false"
                                            >
                                                <i class="ti ti-settings fs-22 align-middle"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <a href="javascript:void(0);" class="dropdown-item">Mark as Read</a>

                                                <a href="javascript:void(0);" class="dropdown-item">Delete All</a>

                                                <a href="javascript:void(0);" class="dropdown-item">Do not Disturb</a>

                                                <a href="javascript:void(0);" class="dropdown-item">Other Settings</a>
                                            </div>
                                        </div>
                                    </div>
                                </b-row>
                            </div>

                            <simplebar class="position-relative card rounded-0 z-2 shadow-none" style="max-height: 300px">
                                <div
                                    v-for="(item, idx) in notifications"
                                    :key="idx"
                                    class="dropdown-item notification-item text-wrap py-2"
                                    :class="{ active: idx === 0 }"
                                >
                                    <span class="d-flex align-items-center">
                                        <span v-if="item.sender" class="position-relative me-3 flex-shrink-0">
                                            <img :src="item.sender.image" class="avatar-md rounded-circle" alt="" />
                                            <span class="position-absolute rounded-pill bg-danger notification-badge">
                                                <i
                                                    :class="
                                                        item.type === 'commented'
                                                            ? 'ti ti-message-circle'
                                                            : item.type === 'donated'
                                                              ? 'ti ti-currency-dollar'
                                                              : item.type === 'followed'
                                                                ? 'ti ti-plus'
                                                                : item.type === 'liked'
                                                                  ? 'ti ti-heart-filled'
                                                                  : ''
                                                    "
                                                ></i>
                                                <span class="visually-hidden">unread messages</span>
                                            </span>
                                        </span>

                                        <div v-else class="avatar-md me-3 flex-shrink-0">
                                            <span class="avatar-title bg-success-subtle text-success rounded-circle fs-22">
                                                <Icon icon="solar:wallet-money-bold-duotone" />
                                            </span>
                                        </div>

                                        <span class="text-muted flex-grow-1">
                                            <span class="fw-medium text-body">{{ item.message }}</span>
                                            <br />
                                            <span class="fs-12">{{ item.timestamp }}</span>
                                        </span>
                                        <span class="notification-item-close">
                                            <button
                                                type="button"
                                                class="btn btn-ghost-danger rounded-circle btn-sm btn-icon"
                                                data-dismissible="#notification-1"
                                            >
                                                <i class="ti ti-x fs-16"></i>
                                            </button>
                                        </span>
                                    </span>
                                </div>
                            </simplebar>

                            <div
                                style="height: 300px"
                                class="d-flex align-items-center justify-content-center position-absolute z-1 bottom-0 end-0 start-0 top-0 text-center"
                            >
                                <div>
                                    <Icon icon="line-md:bell-twotone-alert-loop" class="fs-80 text-secondary mt-2" />
                                    <h4 class="fw-semibold fst-italic lh-base mb-0 mt-3">Hey! 👋 <br />You have no any notifications</h4>
                                </div>
                            </div>

                            <!-- All-->
                            <a
                                href="javascript:void(0);"
                                class="dropdown-item notification-item position-fixed text-reset text-decoration-underline link-offset-2 fw-bold notify-item border-top border-light z-2 bottom-0 py-2 text-center"
                            >
                                View All
                            </a>
                        </div>
                    </DropDown>
                </div>

                <!-- Apps Dropdown -->
                <div class="topbar-item d-none d-sm-flex">
                    <b-dropdown
                        :variant="null"
                        :offset="{ mainAxis: 27 }"
                        no-caret
                        toggle-class="topbar-link p-0"
                        menu-class="p-0 dropdown-menu-lg"
                        end
                    >
                        <template v-slot:button-content>
                            <i class="ti ti-apps fs-22"></i>
                        </template>

                        <div class="p-2">
                            <b-row v-for="(app, idx) in chunkArray(apps, 3)" :key="idx" class="g-0">
                                <b-col v-for="item in app" :key="item.name">
                                    <a class="dropdown-icon-item" href="#">
                                        <img :src="item.image" alt="" />
                                        <span>{{ item.name }}</span>
                                    </a>
                                </b-col>
                            </b-row>
                        </div>
                    </b-dropdown>
                </div>

                <!-- Button Trigger Customizer -->
                <div class="topbar-item d-none d-sm-flex">
                    <button class="topbar-link" type="button" v-b-toggle.customizer>
                        <i class="ti ti-settings fs-22"></i>
                    </button>
                </div>

                <!-- Light/Dark Mode Button -->
                <div class="topbar-item d-none d-sm-flex">
                    <button class="topbar-link" id="light-dark-mode" type="button" @click="toggleTheme">
                        <i class="ti ti-moon fs-22"></i>
                    </button>
                </div>

                <!-- User Dropdown -->
                <div class="topbar-item nav-user">
                    <DropDown>
                        <a
                            class="topbar-link dropdown-toggle drop-arrow-none px-2"
                            data-bs-toggle="dropdown"
                            data-bs-offset="0,19"
                            type="button"
                            aria-haspopup="false"
                            aria-expanded="false"
                        >
                            <img :src="avatar1" width="32" class="rounded-circle me-lg-2 d-flex" alt="user-image" />
                            <span class="d-lg-flex flex-column d-none gap-1">
                                <h5 class="my-0">Dhanoo K.</h5>
                                <h6 class="fw-normal my-0">Premium</h6>
                            </span>
                            <i class="ti ti-chevron-down d-none d-lg-block ms-2 align-middle"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end">
                            <div class="dropdown-header noti-title">
                                <h6 class="text-overflow m-0">Welcome !</h6>
                            </div>

                            <Link v-for="(item, idx) in profileMenuItems" :key="idx" href="" class="dropdown-item">
                                <i :class="item.icon" class="fs-17 me-1 align-middle"></i>
                                <span class="align-middle">{{ item.label }}</span>
                            </Link>
                        </div>
                    </DropDown>
                </div>
            </div>
        </div>
    </header>

    <!-- Search Modal -->
    <b-modal v-model="showSearchModal" size="lg" hide-header hide-footer body-class="p-0">
        <b-card no-body class="mb-0">
            <div class="d-flex align-items-center flex-row px-3 py-2" id="top-search">
                <i class="ti ti-search fs-22"></i>
                <b-form-input type="search" class="border-0" id="search-modal-input" placeholder="Search for actions, people," />
                <b-button :variant="null" type="button" class="p-0" @click="toggleShowSearchModal">[esc]</b-button>
            </div>
        </b-card>
    </b-modal>
</template>

<script setup lang="ts">
import DropDown from '@/components/DropDown.vue';
import LogoBox from '@/components/LogoBox.vue';
import { chunkArray } from '@/helpers/array';
import { toggleDocumentAttribute } from '@/helpers/other';
import MegaMenu from '@/layoutsparam/partials/components/MegaMenu.vue';
import { apps, languages, notifications, profileMenuItems } from '@/layoutsparam/partials/data';
import { useLayoutStore } from '@/stores/layout';
import { Link } from '@inertiajs/vue3';
import simplebar from 'simplebar-vue';
import { onMounted, ref } from 'vue';

import usFlag from '@/images/flags/us.svg';
import avatar1 from '@/images/users/avatar-1.jpg';
import { Icon } from '@iconify/vue';

const showSearchModal = ref(false);

const toggleShowSearchModal = () => {
    showSearchModal.value = !showSearchModal.value;
};

const { layout, setTheme, setLeftSideBarSize, init } = useLayoutStore();

const toggleTheme = () => {
    if (layout.theme === 'light') {
        return setTheme('dark');
    }
    setTheme('light');
};

const toggleLeftSideBar = () => {
    if (layout.leftSideBarSize === 'condensed') {
        return setLeftSideBarSize('default');
    }
    if (layout.leftSideBarSize === 'fullscreen') {
        return setLeftSideBarSize('default');
    }
    if (layout.leftSideBarSize === 'default') {
        return setLeftSideBarSize('condensed');
    }

    toggleDocumentAttribute('class', 'sidebar-enable');
    showBackdrop();
};

onMounted(() => {
    init();
});

const showBackdrop = () => {
    const backdrop = document.createElement('div') as HTMLDivElement;
    backdrop.id = 'backdrop';
    if (backdrop) {
        backdrop.classList.add('offcanvas-backdrop', 'fade', 'show');
        document.body.appendChild(backdrop);
        document.body.style.overflow = 'hidden';
        if (window.innerWidth > 1040) {
            document.body.style.paddingRight = '15px';
        }

        backdrop.addEventListener('click', function () {
            toggleDocumentAttribute('class', '');
            document.body.removeChild(backdrop);
            document.body.style.overflow = '';
            document.body.style.paddingRight = '';
        });
    }
};
</script>
