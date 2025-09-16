<template>
    <VerticalLayout>
        <PageTitle title="E-Wallet" subtitle="Dashboard" />

        <b-row>
            <b-col xxl="9">
                <b-row>
                    <b-col xl="4">
                        <b-card no-body>
                            <div class="d-flex card-header justify-content-between align-items-center">
                                <h4 class="header-title">Total Balance</h4>

                                <b-dropdown :variant="null" no-caret toggle-class="p-0 m-0 card-drop bg-light-subtle rounded">
                                    <template v-slot:button-content>
                                        <i class="ti ti-dots-vertical"></i>
                                    </template>
                                    <b-dropdown-item>Sales Report</b-dropdown-item>
                                    <b-dropdown-item>Export Report</b-dropdown-item>
                                    <b-dropdown-item>Profit</b-dropdown-item>
                                    <b-dropdown-item>Action</b-dropdown-item>
                                </b-dropdown>
                            </div>

                            <b-card-body class="pt-1">
                                <h2 class="fw-bold">
                                    $92,652.36 <a href="#" class="text-muted"><i class="ti ti-eye"></i></a>
                                </h2>
                                <b-row class="g-2 mt-2 pt-1">
                                    <b-col>
                                        <a href="#" class="btn btn-primary w-100"><i class="ti ti-coin me-1"></i> Transfer</a>
                                    </b-col>
                                    <b-col>
                                        <a href="#" class="btn btn-info w-100"><i class="ti ti-coin me-1"></i> Request</a>
                                    </b-col>
                                </b-row>
                            </b-card-body>
                        </b-card>
                    </b-col>

                    <b-col xl="4" v-for="(item, idx) in statistics" :key="idx">
                        <StatisticCardWithChart :item="item" />
                    </b-col>
                </b-row>

                <b-row>
                    <b-col cols="12">
                        <b-card no-body>
                            <b-card-header class="d-flex align-items-center flex-wrap gap-2">
                                <h4 class="header-title mb-0 me-auto">Overview</h4>

                                <a href="#" class="btn btn-soft-primary"> Export <i class="ti ti-file-export ms-1"></i> </a>

                                <div>
                                    <b-input-group>
                                        <FlatPicker
                                            id="date"
                                            v-model="date"
                                            custom-class="border-0 shadow"
                                            :options="{ dateFormat: 'M Y', mode: 'range' }"
                                        />
                                        <b-input-group-text class="bg-primary border-primary text-white">
                                            <i class="ti ti-calendar fs-15"></i>
                                        </b-input-group-text>
                                    </b-input-group>
                                </div>
                            </b-card-header>

                            <b-card-body class="p-0">
                                <div class="bg-light bg-opacity-50">
                                    <b-row class="text-center">
                                        <b-col cols="6" class="col-md">
                                            <p class="text-muted mb-1 mt-3">Revenue</p>
                                            <h4 class="mb-3">
                                                <span class="ti ti-square-rounded-arrow-down text-success me-1"></span>
                                                <span class="fw-semibold">$29.5k</span>
                                            </h4>
                                        </b-col>
                                        <b-col cols="6" class="col-md">
                                            <p class="text-muted mb-1 mt-3">Expenses</p>
                                            <h4 class="mb-3">
                                                <span class="ti ti-square-rounded-arrow-up text-danger me-1"></span>
                                                <span class="fw-semibold">$15.07k</span>
                                            </h4>
                                        </b-col>
                                        <b-col cols="6" class="col-md">
                                            <p class="text-muted mb-1 mt-3">Investment</p>
                                            <h4 class="mb-3">
                                                <span class="ti ti-chart-infographic me-1"></span>
                                                <span class="fw-semibold">$3.6k</span>
                                            </h4>
                                        </b-col>
                                        <b-col cols="6" class="col-md">
                                            <p class="text-muted mb-1 mt-3">Savings</p>
                                            <h4 class="mb-3">
                                                <span class="ti ti-pig me-1"></span>
                                                <span class="fw-semibold">$6.9k</span>
                                            </h4>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div dir="ltr" class="p-2">
                                    <ApexChart :chart="balanceOverviewChart" />
                                </div>
                            </b-card-body>
                        </b-card>
                    </b-col>
                </b-row>
            </b-col>

            <b-col xxl="3">
                <b-row>
                    <b-col md="6" xxl="12">
                        <b-card no-body>
                            <b-card-body>
                                <Swiper
                                    :slides-per-view="1"
                                    :modules="[Pagination]"
                                    :space-between="10"
                                    :pagination="{ el: '.swiper-pagination', clickable: true }"
                                    wrapper-class="mb-2"
                                >
                                    <SwiperSlide v-for="(item, idx) in electronicCards" :key="idx">
                                        <ElectronicCard :item="item" />
                                    </SwiperSlide>
                                    <div class="swiper-pagination"></div>
                                </Swiper>
                            </b-card-body>
                        </b-card>
                    </b-col>

                    <b-col md="6" xxl="12">
                        <b-card no-body>
                            <b-card-header class="d-flex border-bottom align-items-center border-dashed">
                                <h4 class="header-title me-auto">
                                    Quick Transfer
                                    <i
                                        class="ti ti-info-octagon text-muted ms-1"
                                        data-bs-toggle="tooltip"
                                        data-bs-placement="top"
                                        data-bs-title="This top tooltip is themed via CSS variables."
                                    >
                                    </i>
                                </h4>

                                <b-dropdown :variant="null" no-caret toggle-class="p-0 m-0 card-drop bg-light-subtle rounded">
                                    <template v-slot:button-content>
                                        <i class="ti ti-dots-vertical"></i>
                                    </template>
                                    <b-dropdown-item>Sales Report</b-dropdown-item>
                                    <b-dropdown-item>Export Report</b-dropdown-item>
                                    <b-dropdown-item>Profit</b-dropdown-item>
                                    <b-dropdown-item>Action</b-dropdown-item>
                                </b-dropdown>
                            </b-card-header>

                            <b-card-body>
                                <div class="d-flex justify-content-center gap-1">
                                    <div v-for="(user, idx) in quickTransfer" :key="idx" class="avatar">
                                        <img
                                            :src="user.image"
                                            v-b-tooltip.top="user.name"
                                            alt=""
                                            class="rounded-circle img-thumbnail avatar-lg"
                                            :class="idx == 1 && 'border-primary'"
                                        />
                                    </div>
                                </div>

                                <div class="mb-2 mt-3">
                                    <b-form-input type="text" v-model="cardNumber" id="cardNumber" placeholder="Card Number" />
                                </div>

                                <div class="mb-2 mt-3">
                                    <b-form-input type="text" id="enterAmount" label="Enter Amount" v-model="amount" />
                                </div>

                                <b-row class="g-2 mt-3">
                                    <b-col>
                                        <a href="#" class="btn btn-primary w-100">Send Money</a>
                                    </b-col>
                                    <b-col>
                                        <a href="#" class="btn btn-outline-info w-100">Save as Draft</a>
                                    </b-col>
                                </b-row>
                            </b-card-body>
                        </b-card>
                    </b-col>
                </b-row>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center flex-wrap gap-2">
                        <h4 class="header-title me-auto">Transactions <span class="text-muted fw-normal fs-14">(95.6k+ Transactions)</span></h4>

                        <div class="search-bar">
                            <b-form-input size="sm" class="search" placeholder="Search Here..." />
                        </div>

                        <div class="w-auto">
                            <b-form-select size="sm" v-model="selectedOption" :options="transactionOptions" />
                        </div>

                        <a href="javascript:void(0);" class="btn btn-sm btn-soft-primary">Export <i class="ti ti-file-export ms-1"></i></a>
                    </b-card-header>

                    <b-card-body class="p-0">
                        <div class="table-card">
                            <b-table-simple responsive hover borderless class="table-custom table-nowrap mb-0 align-middle">
                                <b-thead class="bg-light thead-sm bg-opacity-50">
                                    <b-tr class="text-uppercase fs-12">
                                        <b-th v-for="(header, idx) in transactionsTable.header" :key="idx" scope="col" class="text-muted">
                                            {{ header }}
                                        </b-th>
                                    </b-tr>
                                </b-thead>

                                <b-tbody>
                                    <b-tr v-for="(item, idx) in transactionsTable.body" :key="idx">
                                        <b-td>
                                            <a href="#" class="fw-medium text-reset">#{{ item.id }}</a>
                                        </b-td>
                                        <b-td>
                                            <img v-if="item.user.image" :src="item.user.image" alt="" class="avatar-xs rounded-circle me-1" />
                                            <div v-else class="avatar-xs d-inline-block me-1">
                                                <span class="avatar-title bg-primary-subtle text-primary fw-semibold rounded-circle">
                                                    {{ getFirstCharacter(item.user.name) }}
                                                </span>
                                            </div>
                                            <a href="#" class="text-reset">{{ item.user.name }}</a>
                                        </b-td>
                                        <b-td>{{ item.description }}</b-td>
                                        <b-td :class="item.amount > 0 ? 'text-success' : 'text-danger'">
                                            <span v-if="item.amount < 0">-</span>
                                            {{ currency }}{{ Math.abs(item.amount) }}
                                        </b-td>
                                        <b-td
                                            >{{ item.timestamp.date }} <small class="text-muted">{{ item.timestamp.time }}</small></b-td
                                        >
                                        <b-td>{{ toSentenceCase(item.type) }}</b-td>
                                        <b-td>
                                            <img :src="item.paymentMethod.image" alt="" height="24" class="me-1" />
                                            <span v-if="item.paymentMethod.lastDigits" class="align-middle">
                                                *{{ item.paymentMethod.lastDigits }}
                                            </span>
                                            <span v-if="item.paymentMethod.name" class="align-middle">{{ item.paymentMethod.name }}</span>
                                        </b-td>
                                        <b-td>
                                            <b-badge
                                                :variant="null"
                                                class="p-1"
                                                :class="
                                                    item.status === 'success'
                                                        ? 'bg-success-subtle text-success'
                                                        : item.status === 'failed'
                                                          ? 'bg-danger-subtle text-danger'
                                                          : 'bg-warning-subtle text-warning'
                                                "
                                            >
                                                {{ kebabToTitleCase(item.status) }}
                                            </b-badge>
                                        </b-td>
                                        <b-td>
                                            <a href="#" class="text-muted fs-20"> <i class="ti ti-edit"></i></a>
                                        </b-td>
                                    </b-tr>
                                </b-tbody>
                            </b-table-simple>
                        </div>
                    </b-card-body>

                    <b-card-footer class="border-top border-light">
                        <div class="align-items-center justify-content-between row text-sm-start text-center">
                            <div class="col-sm">
                                <div class="text-muted">
                                    Showing <span class="fw-semibold text-body"> 7 </span> of <span class="fw-semibold"> 15 </span>
                                    Transactions
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
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import FlatPicker from '@/components/FlatPicker.vue';
import PageTitle from '@/components/PageTitle.vue';
import { currency } from '@/helpers';
import { getFirstCharacter, kebabToTitleCase, toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { balanceOverviewChart, electronicCards, quickTransfer, statistics, transactionsTable } from '@/pages/dashboards/e-wallet/components/data';
import ElectronicCard from '@/pages/dashboards/e-wallet/components/ElectronicCard.vue';
import StatisticCardWithChart from '@/pages/dashboards/e-wallet/components/StatisticCardWithChart.vue';
import { Pagination } from 'swiper/modules';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { ref } from 'vue';

const date = ref('Jun to Aug');

const selectedOption = ref('All');
const transactionOptions = [
    { value: 'All', text: 'All' },
    { value: 'Paid', text: 'Paid' },
    { value: 'Cancelled', text: 'Cancelled' },
    { value: 'Failed', text: 'Failed' },
    { value: 'OnHold', text: 'OnHold' },
];

const cardNumber = ref('3658 9857 5820 0039');
const amount = ref(963.25);
</script>
