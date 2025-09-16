<template>
    <div class="sidenav-menu">
        <LogoBox />

        <!-- Sidebar Hover Menu Toggle Button -->
        <button class="button-sm-hover" @click="toggleMenuSize">
            <i class="ti ti-circle align-middle"></i>
        </button>

        <!-- Full Sidebar Menu Close Button -->
        <button class="button-close-fullsidebar" @click="closeLeftSideBar">
            <i class="ti ti-x align-middle"></i>
        </button>

        <simplebar>
            <VerticalMenu />

            <!-- Help Box -->
            <div class="help-box text-center">
                <img :src="coffeeCup" height="90" alt="Helper Icon Image" />
                <h5 class="fw-semibold fs-16 mt-3">DDM PARM</h5>
                <!-- <p class="text-muted mb-3">Upgrade to plan to get access to unlimited reports</p>
                <a href="#" class="btn btn-danger btn-sm">Upgrade</a> -->
            </div>

            <div class="clearfix"></div>
        </simplebar>
    </div>
</template>

<script setup lang="ts">
import LogoBox from '@/components/LogoBox.vue';
import { toggleDocumentAttribute } from '@/helpers/other';
import coffeeCup from '@/images/coffee-cup.svg';
import VerticalMenu from '@/layoutsparam/partials/components/VerticalMenu.vue';
import { useLayoutStore } from '@/stores/layout';
import simplebar from 'simplebar-vue';
import { onMounted } from 'vue';

const { layout, setLeftSideBarSize } = useLayoutStore();

const toggleMenuSize = () => {
    if (layout.leftSideBarSize === 'sm-hover-active') return setLeftSideBarSize('sm-hover');
    return setLeftSideBarSize('sm-hover-active');
};

const resize = () => {
    if (window.innerWidth < 770) {
        setLeftSideBarSize('full');
    } else if (window.innerWidth < 1140) {
        setLeftSideBarSize('condensed');
    } else {
        setLeftSideBarSize(layout.leftSideBarSize === 'condensed' || layout.leftSideBarSize === 'full' ? 'sm-hover-active' : layout.leftSideBarSize);
    }
};

onMounted(() => {
    resize();
    window.addEventListener('resize', () => {
        resize();
    });
});

const closeLeftSideBar = () => {
    toggleDocumentAttribute('class', '');
    const backdrop = document.getElementById('backdrop');
    if (backdrop) {
        document.body.removeChild(backdrop);
    }
};
</script>
