<template>
    <VerticalLayout>
        <Head title="Sales" />
        <b-row>
            <b-col cols="12">
                <div class="page-title-head d-flex align-items-sm-center flex-sm-row flex-column">
                    <div class="flex-grow-1">
                        <h4 class="fs-18 fw-semibold m-0">Dashboard</h4>
                    </div>
                    <div class="mt-sm-0 mt-3">
                        <b-form>
                            <b-row class="g-2 align-items-center mb-0">
                                <div class="col-auto">
                                    <a href="#" class="btn btn-light"> <i class="ti ti-sort-ascending me-1"></i> Sort By </a>
                                </div>

                                <div class="col-sm-auto">
                                    <b-input-group>
                                        <FlatPicker
                                            id="date"
                                            v-model="date"
                                            custom-class="border-0 shadow"
                                            :options="{ dateFormat: 'd M', mode: 'range' }"
                                        />
                                        <b-input-group-text class="bg-primary border-primary text-white">
                                            <i class="ti ti-calendar fs-15"></i>
                                        </b-input-group-text>
                                    </b-input-group>
                                </div>
                            </b-row>
                        </b-form>
                    </div>
                </div>
            </b-col>
        </b-row>

        <b-row>
            <b-col>
                <b-row class="row-cols-xxl-4 row-cols-md-2 row-cols-1 text-center">
                    <b-col v-for="(item, idx) in statistics" :key="idx">
                        <StatisticCard :item="item" />
                    </b-col>
                </b-row>

                <b-row>
                    <b-col xxl="8">
                        <b-card no-body>
                            <b-card-header class="d-flex justify-content-between align-items-center">
                                <h4 class="header-title">Overview</h4>
                                <b-dropdown :variant="null" no-caret toggle-class="p-0 m-0 card-drop">
                                    <template v-slot:button-content>
                                        <i class="ti ti-dots-vertical"></i>
                                    </template>
                                    <b-dropdown-item>Sales Report</b-dropdown-item>
                                    <b-dropdown-item>Export Report</b-dropdown-item>
                                    <b-dropdown-item>Profit</b-dropdown-item>
                                    <b-dropdown-item>Action</b-dropdown-item>
                                </b-dropdown>
                            </b-card-header>

                            <div class="bg-light bg-opacity-50">
                                <b-row class="text-center">
                                    <b-col md="3" cols="6">
                                        <p class="text-muted mb-1 mt-3">Revenue</p>
                                        <h4 class="mb-3">
                                            <span class="ti ti-square-rounded-arrow-down text-success me-1"></span>
                                            <span>$29.5k</span>
                                        </h4>
                                    </b-col>
                                    <b-col md="3" cols="6">
                                        <p class="text-muted mb-1 mt-3">Expenses</p>
                                        <h4 class="mb-3">
                                            <span class="ti ti-square-rounded-arrow-up text-danger me-1"></span>
                                            <span>$15.07k</span>
                                        </h4>
                                    </b-col>
                                    <b-col md="3" cols="6">
                                        <p class="text-muted mb-1 mt-3">Investment</p>
                                        <h4 class="mb-3">
                                            <span class="ti ti-chart-infographic me-1"></span>
                                            <span>$3.6k</span>
                                        </h4>
                                    </b-col>
                                    <b-col md="3" cols="6">
                                        <p class="text-muted mb-1 mt-3">Savings</p>
                                        <h4 class="mb-3">
                                            <span class="ti ti-pig me-1"></span>
                                            <span>$6.9k</span>
                                        </h4>
                                    </b-col>
                                </b-row>
                            </div>

                            <div class="card-body pt-0">
                                <div dir="ltr">
                                    <ApexChart :chart="overviewChart" />
                                </div>
                            </div>
                        </b-card>
                    </b-col>

                    <b-col xxl="4">
                        <div class="card">
                            <b-card-header class="d-flex justify-content-between align-items-center border-bottom border-dashed">
                                <h4 class="header-title">Top Traffic by Source</h4>

                                <b-dropdown :variant="null" no-caret toggle-class="p-0 m-0 card-drop">
                                    <template v-slot:button-content>
                                        <i class="ti ti-dots-vertical"></i>
                                    </template>
                                    <b-dropdown-item>Refresh Report</b-dropdown-item>
                                    <b-dropdown-item>Export Report</b-dropdown-item>
                                </b-dropdown>
                            </b-card-header>

                            <b-card-body>
                                <ApexChart :chart="trafficBySourceChart" />

                                <b-row class="mt-2">
                                    <b-col>
                                        <div class="d-flex justify-content-between align-items-center p-1">
                                            <div>
                                                <i class="ti ti-circle-filled fs-12 text-primary me-1 align-middle"></i>
                                                <span class="fw-semibold align-middle">Direct</span>
                                            </div>
                                            <span class="fw-semibold text-muted float-end"
                                                ><i class="ti ti-arrow-badge-down text-danger"></i> 965</span
                                            >
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center p-1">
                                            <div>
                                                <i class="ti ti-circle-filled fs-12 text-success me-1 align-middle"></i>
                                                <span class="fw-semibold align-middle">Social</span>
                                            </div>
                                            <span class="fw-semibold text-muted float-end"><i class="ti ti-arrow-badge-up text-success"></i> 75</span>
                                        </div>
                                    </b-col>
                                    <b-col>
                                        <div class="d-flex justify-content-between align-items-center p-1">
                                            <div>
                                                <i class="ti ti-circle-filled fs-12 text-secondary me-1 align-middle"></i>
                                                <span class="fw-semibold align-middle"> Marketing</span>
                                            </div>
                                            <span class="fw-semibold text-muted float-end"
                                                ><i class="ti ti-arrow-badge-up text-success"></i> 102</span
                                            >
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center p-1">
                                            <div>
                                                <i class="ti ti-circle-filled fs-12 text-danger me-1 align-middle"></i>
                                                <span class="fw-semibold align-middle">Affiliates</span>
                                            </div>
                                            <span class="fw-semibold text-muted float-end"
                                                ><i class="ti ti-arrow-badge-down text-danger"></i> 96</span
                                            >
                                        </div>
                                    </b-col>
                                </b-row>
                            </b-card-body>
                        </div>
                    </b-col>
                </b-row>

                <b-row>
                    <b-col xxl="6">
                        <b-card no-body>
                            <b-card-header class="d-flex justify-content-between align-items-center">
                                <h4 class="header-title">Brands Listing</h4>
                                <a href="javascript:void(0);" class="btn btn-sm btn-light">Add Brand <i class="ti ti-plus ms-1"></i></a>
                            </b-card-header>
                            <b-card-body class="p-0">
                                <div class="bg-light bg-opacity-50 py-1 text-center">
                                    <p class="m-0"><b>69</b> Active brands out of <span class="fw-medium">102</span></p>
                                </div>
                                <b-table-simple small hover responsive class="table-custom table-centered table-sm table-nowrap mb-0">
                                    <b-tbody>
                                        <b-tr v-for="(brand, idx) in brandListingTable" :key="idx">
                                            <b-td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar-md me-2 flex-shrink-0">
                                                        <span class="avatar-title bg-primary-subtle rounded-circle">
                                                            <img :src="brand.image" alt="" height="22" />
                                                        </span>
                                                    </div>
                                                    <div>
                                                        <span class="text-muted fs-12">{{ toSentenceCase(brand.specialistIn) }}</span> <br />
                                                        <h5 class="fs-14 mt-1">{{ brand.name }}</h5>
                                                    </div>
                                                </div>
                                            </b-td>
                                            <b-td>
                                                <span class="text-muted fs-12">Established</span>
                                                <h5 class="fs-14 fw-normal mt-1">Since {{ brand.establishedIn }}</h5>
                                            </b-td>
                                            <b-td>
                                                <span class="text-muted fs-12">Stores</span> <br />
                                                <h5 class="fs-14 fw-normal mt-1">{{ brand.stores }}</h5>
                                            </b-td>
                                            <b-td>
                                                <span class="text-muted fs-12">Products</span>
                                                <h5 class="fs-14 fw-normal mt-1">{{ brand.products }}</h5>
                                            </b-td>
                                            <b-td>
                                                <span class="text-muted fs-12">Status</span>
                                                <h5 class="fs-14 fw-normal mt-1">
                                                    <i
                                                        class="ti ti-circle-filled fs-12"
                                                        :class="brand.status === 'active' ? 'text-success' : 'text-danger'"
                                                    ></i>
                                                    {{ toSentenceCase(brand.status) }}
                                                </h5>
                                            </b-td>
                                            <b-td style="width: 30px">
                                                <b-dropdown :variant="null" no-caret toggle-class="text-muted card-drop p-0">
                                                    <template #button-content>
                                                        <i class="ti ti-dots-vertical"></i>
                                                    </template>
                                                    <b-dropdown-item>Refresh Report</b-dropdown-item>
                                                    <b-dropdown-item>Export Report</b-dropdown-item>
                                                </b-dropdown>
                                            </b-td>
                                        </b-tr>
                                    </b-tbody>
                                </b-table-simple>
                            </b-card-body>

                            <b-card-footer>
                                <div class="align-items-center justify-content-between row text-sm-start text-center">
                                    <div class="col-sm">
                                        <div class="text-muted">
                                            Showing <span class="fw-semibold">5</span> of <span class="fw-semibold">15</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-sm-0 mt-3">
                                        <ul class="pagination pagination-boxed pagination-sm justify-content-center mb-0">
                                            <li class="page-item disabled">
                                                <a href="#" class="page-link"><i class="ti ti-chevron-left"></i></a>
                                            </li>
                                            <li class="page-item active">
                                                <a href="#" class="page-link">1</a>
                                            </li>
                                            <li class="page-item">
                                                <a href="#" class="page-link">2</a>
                                            </li>
                                            <li class="page-item">
                                                <a href="#" class="page-link">3</a>
                                            </li>
                                            <li class="page-item">
                                                <a href="#" class="page-link"><i class="ti ti-chevron-right"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </b-card-footer>
                        </b-card>
                    </b-col>

                    <b-col xxl="6">
                        <b-card no-body class="card-h-100">
                            <b-card-header class="d-flex align-items-center border-bottom flex-wrap gap-2 border-dashed">
                                <h4 class="header-title me-auto">Top Selling Products</h4>

                                <div class="d-flex justify-content-end gap-2 text-end">
                                    <a href="javascript:void(0);" class="btn btn-sm btn-light">Import <i class="ti ti-download ms-1"></i></a>
                                    <a href="javascript:void(0);" class="btn btn-sm btn-primary">Export <i class="ti ti-file-export ms-1"></i></a>
                                </div>
                            </b-card-header>

                            <b-card-body class="p-0">
                                <b-table-simple hover class="table-custom table-nowrap mb-0 align-middle">
                                    <b-tbody>
                                        <b-tr v-for="(product, idx) in topSellingProductsTable" :key="idx">
                                            <b-td>
                                                <div class="avatar-lg">
                                                    <img :src="product.image" alt="Product-1" class="img-fluid rounded-2" />
                                                </div>
                                            </b-td>
                                            <b-td class="ps-0">
                                                <h5 class="fs-14 my-1">
                                                    <Link :href="product.url" class="link-reset">
                                                        {{ product.name }}
                                                    </Link>
                                                </h5>
                                                <span class="text-muted fs-12">{{ product.createdAt }}</span>
                                            </b-td>
                                            <b-td>
                                                <h5 class="fs-14 my-1">{{ currency }}{{ product.price }}</h5>
                                                <span class="text-muted fs-12">Price</span>
                                            </b-td>
                                            <b-td>
                                                <h5 class="fs-14 my-1">{{ product.quantity }}</h5>
                                                <span class="text-muted fs-12">Quantity</span>
                                            </b-td>
                                            <b-td>
                                                <div class="d-flex align-items-center justify-content-end">
                                                    <div class="me-2">
                                                        <h5 class="fs-14 my-1">{{ currency }}{{ product.totalEarning }}</h5>
                                                        <span class="text-muted fs-12">Amount</span>
                                                    </div>
                                                </div>
                                            </b-td>
                                        </b-tr>
                                    </b-tbody>
                                </b-table-simple>
                            </b-card-body>

                            <b-card-footer>
                                <div class="align-items-center justify-content-between row text-sm-start text-center">
                                    <div class="col-sm">
                                        <div class="text-muted">
                                            Showing <span class="fw-semibold">5</span> of <span class="fw-semibold">10</span> Results
                                        </div>
                                    </div>
                                    <div class="col-sm-auto mt-sm-0 mt-3">
                                        <ul class="pagination pagination-boxed pagination-sm justify-content-center mb-0">
                                            <li class="page-item disabled">
                                                <a href="#" class="page-link"><i class="ti ti-chevron-left"></i></a>
                                            </li>
                                            <li class="page-item active">
                                                <a href="#" class="page-link">1</a>
                                            </li>
                                            <li class="page-item">
                                                <a href="#" class="page-link">2</a>
                                            </li>
                                            <li class="page-item">
                                                <a href="#" class="page-link"><i class="ti ti-chevron-right"></i></a>
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </b-card-footer>
                        </b-card>
                    </b-col>
                </b-row>
            </b-col>

            <div class="info-sidebar col-auto">
                <b-alert v-model="showAlert" variant="primary" class="d-flex align-items-center">
                    <Icon icon="solar:help-bold-duotone" class="fs-24 me-1" />
                    <b>Help line:</b> <span class="fw-medium ms-1">+(012) 123 456 78</span>
                </b-alert>

                <b-card no-body class="card bg-primary">
                    <b-card-body
                        :style="{
                            backgroundImage: `url(${arrows})`,
                            backgroundSize: 'contain',
                            backgroundRepeat: 'no-repeat',
                            backgroundPosition: 'right bottom',
                        }"
                    >
                        <h1><i class="ti ti-receipt-tax text-white"></i></h1>
                        <h4 class="text-white">Estimated tax for this year</h4>
                        <p class="text-white text-opacity-75">We kindly encourage you to review your recent transactions</p>
                        <a href="#!" class="btn btn-sm rounded-pill btn-info">Activate Now</a>
                    </b-card-body>
                </b-card>

                <b-card no-body>
                    <b-card-body>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="header-title">Recent Orders:</h4>
                            <div>
                                <a href="javascript:void(0);" class="btn btn-sm btn-primary rounded-circle btn-icon"><i class="ti ti-plus"></i></a>
                            </div>
                        </div>

                        <div v-for="(item, idx) in recentOrders" :key="idx" class="d-flex align-items-center position-relative mb-2 gap-2">
                            <div class="avatar-md flex-shrink-0">
                                <img :src="item.image" alt="product-pic" height="36" />
                            </div>
                            <div>
                                <h5 class="fs-14 my-1">
                                    <Link :href="item.url" class="stretched-link link-reset">
                                        {{ item.name }}
                                    </Link>
                                </h5>
                                <span class="text-muted fs-12">
                                    {{ currency }}{{ item.price }} x {{ item.quantity }} = {{ currency }}{{ item.price * item.quantity }}
                                </span>
                            </div>
                            <div class="ms-auto">
                                <b-badge
                                    class="px-2 py-1"
                                    :variant="null"
                                    :class="item.status === 'sold' ? 'badge-soft-success' : 'badge-soft-danger'"
                                >
                                    {{ toSentenceCase(item.status) }}
                                </b-badge>
                            </div>
                        </div>

                        <div class="mt-3 text-center">
                            <a href="#!" class="text-decoration-underline fw-semibold link-offset-2 link-dark ms-auto">View All</a>
                        </div>
                    </b-card-body>
                    <b-card-body class="border-top border-dashed p-0">
                        <h4 class="header-title mb-2 mt-3 px-3">Recent Activity:</h4>
                        <simplebar class="my-3 px-3" style="max-height: 370px">
                            <div class="timeline-alt py-0">
                                <div v-for="(item, idx) in recentActivity" :key="idx" class="timeline-item">
                                    <i
                                        class="timeline-icon"
                                        :class="`${item.icon} ${idx % 2 == 0 ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary'}`"
                                    ></i>
                                    <div class="timeline-item-info">
                                        <Link :href="item.url" class="link-reset fw-semibold d-block mb-1">{{ item.name }} </Link>
                                        <span class="mb-1">{{ item.description }}</span>
                                        <p class="mb-0 pb-3">
                                            <small class="text-muted">{{ item.timestamp }}</small>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </simplebar>
                    </b-card-body>
                </b-card>
            </div>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import FlatPicker from '@/components/FlatPicker.vue';
import { currency } from '@/helpers';
import { toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import {
    brandListingTable,
    overviewChart,
    recentActivity,
    recentOrders,
    statistics,
    topSellingProductsTable,
    trafficBySourceChart,
} from '@/pages/dashboards/sales/components/data';
import StatisticCard from '@/pages/dashboards/sales/components/StatisticCard.vue';
import { Icon } from '@iconify/vue';
import { Head, Link } from '@inertiajs/vue3';
import simplebar from 'simplebar-vue';
import { ref } from 'vue';

import arrows from '@/images/png/arrows.svg';

const date = ref('01 May to 15 May');
const showAlert = ref(true);
</script>
