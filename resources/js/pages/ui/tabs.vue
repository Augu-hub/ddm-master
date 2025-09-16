<template>
    <VerticalLayout>
        <PageTitle title="Tabs" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Default Tabs">
                    <b-tabs nav-class="mb-3">
                        <b-tab title="Home" id="home">
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>
                                Welcome to our website! We are dedicated to providing you with the best products and services to enhance your home.
                                Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with our
                                selection of home decor, or tackle those DIY projects with our range of tools and supplies.
                            </p>
                        </b-tab>
                        <b-tab title="Profile" id="profile" active>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>
                                "Hi there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the power of
                                continuous learning and enjoy challenging myself to grow both personally and professionally.
                            </p>
                        </b-tab>
                        <b-tab title="Settings" id="settings">
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span>
                                In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a welcoming
                                atmosphere that beckons passersby to step inside.
                            </p>
                        </b-tab>
                        <b-tab title="Disabled" disabled />
                    </b-tabs>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Tabs Justified">
                    <b-tabs nav-class="mb-3" pills justified>
                        <b-tab title="Home" id="home">
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>
                                Welcome to our website! We are dedicated to providing you with the best products and services to enhance your home.
                                Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with our
                                selection of home decor, or tackle those DIY projects with our range of tools and supplies.
                            </p>
                        </b-tab>
                        <b-tab title="Profile" id="profile" active>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>
                                "Hi there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the power of
                                continuous learning and enjoy challenging myself to grow both personally and professionally.
                            </p>
                        </b-tab>
                        <b-tab title="Settings" id="settings">
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span> In
                                the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic buildings.
                                Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a welcoming atmosphere
                                that beckons passersby to step inside.
                            </p>
                        </b-tab>
                    </b-tabs>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Tabs Vertical Left">
                    <b-row>
                        <b-tabs content-class="pt-0" nav-class="text-left" nav-wrapper-class="col-sm-3 mb-2 mb-sm-0" pills vertical justified>
                            <b-tab id="home" active>
                                <template #title> <i class="ti ti-home fs-18 me-1"></i> Home </template>
                                <p class="mb-0">
                                    <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>
                                    Welcome to our website! We are dedicated to providing you with the best products and services to enhance your
                                    home. Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with
                                    our selection of home decor, or tackle those DIY projects with our range of tools and supplies. Explore our wide
                                    variety of products and find exactly what you need to make your house feel like a home. With our affordable prices
                                    and high-quality items.
                                </p>
                            </b-tab>
                            <b-tab id="profile">
                                <template #title> <i class="ti ti-user-circle fs-18 me-1"></i> Profile </template>
                                <p class="mb-0">
                                    <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>
                                    Hi there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                    interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the
                                    power of continuous learning and enjoy challenging myself to grow both personally and professionally. Outside of
                                    my pursuits, you can often find me immersed in a good book, exploring the outdoors, or experimenting in the
                                    kitchen.
                                </p>
                            </b-tab>
                            <b-tab title="Settings" id="settings">
                                <template #title> <i class="ti ti-settings fs-18 me-1"></i> Settings </template>
                                <p class="mb-0">
                                    <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span>
                                    In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                    buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a
                                    welcoming atmosphere that beckons passersby to step inside.
                                </p>
                            </b-tab>
                        </b-tabs>
                    </b-row>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Tabs Vertical Right">
                    <b-row>
                        <b-col sm="9">
                            <div class="tab-content" id="v-pills-tabContent-right">
                                <div class="tab-pane fade" :class="{ 'active show': tabsVertical === 1 }" id="v-pills-home2">
                                    <p class="mb-0">
                                        <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span
                                        >Welcome to our website! We are dedicated to providing you with the best products and services to enhance your
                                        home. Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere
                                        with our selection of home decor, or tackle those DIY projects with our range of tools and supplies. Explore
                                        our wide variety of products and find exactly what you need to make your house feel like a home. With our
                                        affordable prices and high-quality items.
                                    </p>
                                </div>
                                <div class="tab-pane fade" :class="{ 'active show': tabsVertical === 2 }" id="v-pills-profile2">
                                    <p class="mb-0">
                                        <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>Hi
                                        there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                        interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the
                                        power of continuous learning and enjoy challenging myself to grow both personally and professionally. Outside
                                        of my pursuits, you can often find me immersed in a good book, exploring the outdoors, or experimenting in the
                                        kitchen.
                                    </p>
                                </div>
                                <div class="tab-pane fade" :class="{ 'active show': tabsVertical === 3 }" id="v-pills-settings2">
                                    <p class="mb-0">
                                        <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1"
                                            >S</span
                                        >In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                        buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a
                                        welcoming atmosphere that beckons passersby to step inside.
                                    </p>
                                </div>
                            </div>
                        </b-col>

                        <b-col sm="3" class="mt-sm-0 mt-2">
                            <div class="nav flex-column nav-pills nav-pills-secondary" id="v-pills-tab2" role="tablist" aria-orientation="vertical">
                                <a
                                    class="nav-link"
                                    :class="{ 'active show': tabsVertical === 1 }"
                                    id="v-pills-home-tab2"
                                    @click="() => (tabsVertical = 1)"
                                >
                                    <i class="ti ti-home fs-18 me-1"></i>
                                    <span class="d-none d-md-inline-block">Home</span>
                                </a>
                                <a
                                    class="nav-link"
                                    :class="{ 'active show': tabsVertical === 2 }"
                                    id="v-pills-profile-tab2"
                                    @click="() => (tabsVertical = 2)"
                                >
                                    <i class="ti ti-user-circle fs-18 me-1"></i>
                                    <span class="d-none d-md-inline-block">Profile</span>
                                </a>
                                <a
                                    class="nav-link"
                                    :class="{ 'active show': tabsVertical === 3 }"
                                    id="v-pills-settings-tab2"
                                    @click="() => (tabsVertical = 3)"
                                >
                                    <i class="ti ti-settings fs-18 me-1"></i>
                                    <span class="d-none d-md-inline-block">Settings</span>
                                </a>
                            </div>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Tabs Bordered">
                    <b-tabs nav-class="nav-bordered mb-3">
                        <b-tab id="home">
                            <template #title>
                                <i class="ti ti-home fs-18 me-md-1"></i>
                                <span class="d-none d-md-inline-block">Home</span>
                            </template>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>
                                Welcome to our website! We are dedicated to providing you with the best products and services to enhance your home.
                                Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with our
                                selection of home decor, or tackle those DIY projects with our range of tools and supplies.
                            </p>
                        </b-tab>
                        <b-tab id="profile" active>
                            <template #title>
                                <i class="ti ti-user-circle fs-18 me-md-1"></i>
                                <span class="d-none d-md-inline-block">Profile</span>
                            </template>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>
                                "Hi there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the power of
                                continuous learning and enjoy challenging myself to grow both personally and professionally.
                            </p>
                        </b-tab>
                        <b-tab id="settings">
                            <template #title>
                                <i class="ti ti-settings fs-18 me-md-1"></i>
                                <span class="d-none d-md-inline-block">Settings</span>
                            </template>

                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span>
                                In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a welcoming
                                atmosphere that beckons passersby to step inside.
                            </p>
                        </b-tab>
                    </b-tabs>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Tabs Bordered Justified">
                    <b-tabs nav-class="nav-bordered nav-bordered-danger mb-3" justified>
                        <b-tab id="home">
                            <template #title>
                                <i class="ti ti-home fs-18 me-md-1"></i>
                                <span class="d-none d-md-inline-block">Home</span>
                            </template>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>
                                Welcome to our website! We are dedicated to providing you with the best products and services to enhance your home.
                                Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with our
                                selection of home decor, or tackle those DIY projects with our range of tools and supplies.
                            </p>
                        </b-tab>
                        <b-tab id="profile" active>
                            <template #title>
                                <i class="ti ti-user-circle fs-18 me-md-1"></i>
                                <span class="d-none d-md-inline-block">Profile</span>
                            </template>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>
                                "Hi there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the power of
                                continuous learning and enjoy challenging myself to grow both personally and professionally.
                            </p>
                        </b-tab>
                        <b-tab id="settings">
                            <template #title>
                                <i class="ti ti-settings fs-18 me-md-1"></i>
                                <span class="d-none d-md-inline-block">Settings</span>
                            </template>

                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span>
                                In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a welcoming
                                atmosphere that beckons passersby to step inside.
                            </p>
                        </b-tab>
                    </b-tabs>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Icons Tabs">
                    <b-tabs nav-class="nav-bordered nav-bordered-success mb-3">
                        <b-tab id="home">
                            <template #title>
                                <Icon icon="solar:home-2-bold-duotone" class="fs-24 align-middle" />
                            </template>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>
                                Welcome to our website! We are dedicated to providing you with the best products and services to enhance your home.
                                Whether you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with our
                                selection of home decor, or tackle those DIY projects with our range of tools and supplies.
                            </p>
                        </b-tab>
                        <b-tab id="profile" active>
                            <template #title>
                                <Icon icon="solar:user-id-bold-duotone" class="fs-24 align-middle" />
                            </template>
                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span>
                                "Hi there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the power of
                                continuous learning and enjoy challenging myself to grow both personally and professionally.
                            </p>
                        </b-tab>
                        <b-tab id="settings">
                            <template #title>
                                <Icon icon="solar:settings-bold-duotone" class="fs-24 align-middle" />
                            </template>

                            <p class="mb-0">
                                <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span>
                                In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a welcoming
                                atmosphere that beckons passersby to step inside.
                            </p>
                        </b-tab>
                    </b-tabs>
                </UICard>
            </b-col>

            <b-col xl="6">
                <b-card no-body>
                    <b-card-header class="card-tabs d-flex align-items-center">
                        <div class="flex-grow-1">
                            <h4 class="header-title">Card with Tabs</h4>
                        </div>
                        <ul class="nav nav-tabs nav-justified card-header-tabs nav-bordered">
                            <li class="nav-item">
                                <a href="#home-ct" class="nav-link" :class="{ active: tabsCards === 1 }" @click="() => (tabsCards = 1)">
                                    <i class="ti ti-home d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Home</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#profile-ct" class="nav-link" :class="{ active: tabsCards === 2 }" @click="() => (tabsCards = 2)">
                                    <i class="ti ti-user-circle d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Profile</span>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="#settings-ct" class="nav-link" :class="{ active: tabsCards === 3 }" @click="() => (tabsCards = 3)">
                                    <i class="ti ti-settings d-md-none d-block"></i>
                                    <span class="d-none d-md-block">Settings</span>
                                </a>
                            </li>
                        </ul>
                    </b-card-header>

                    <b-card-body>
                        <div class="tab-content">
                            <div class="tab-pane" :class="{ 'show active': tabsCards === 1 }" id="home-ct">
                                <p class="mb-0">
                                    <span class="fw-semibold d-inline-block bg-info-subtle text-info float-start me-1 rounded px-1">H</span>Welcome to
                                    our website! We are dedicated to providing you with the best products and services to enhance your home. Whether
                                    you're looking to spruce up your living space with stylish furniture, create a cozy atmosphere with our selection
                                    of home decor, or tackle those DIY projects with our range of tools and supplies.
                                </p>
                            </div>
                            <div class="tab-pane" :class="{ 'show active': tabsCards === 2 }" id="profile-ct">
                                <p class="mb-0">
                                    <span class="fw-semibold d-inline-block bg-danger-subtle text-danger float-start me-1 rounded px-1">P</span> "Hi
                                    there! I'm a passionate individual who loves to explore new ideas and connect with like-minded people. My
                                    interests span a wide range of topics including technology, literature, travel, and fitness. I believe in the
                                    power of continuous learning and enjoy challenging myself to grow both personally and professionally.
                                </p>
                            </div>
                            <div class="tab-pane" :class="{ 'show active': tabsCards === 3 }" id="settings-ct">
                                <p class="mb-0">
                                    <span class="fw-semibold d-inline-block bg-secondary-subtle text-secondary float-start me-1 rounded px-1">S</span
                                    >In the heart of a bustling city lies a quaint little cafe, nestled between towering skyscrapers and historic
                                    buildings. Its cozy interior boasts warm, earthy tones accented with splashes of vibrant colors, creating a
                                    welcoming atmosphere that beckons passersby to step inside.
                                </p>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';
const tabsVertical = ref(1);
const tabsCards = ref(2);
</script>
