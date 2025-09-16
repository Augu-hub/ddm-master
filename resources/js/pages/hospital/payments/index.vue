<template>
    <VerticalLayout>
        <PageTitle title="Payments" subtitle="Hospital" />

        <b-row>
            <b-col xxl="3" xl="6" md="6" v-for="(item, idx) in statistics" :key="idx">
                <StatisticCard :item="item" />
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-light">
                        <b-card-title tag="h4" class="mb-0">Payment List</b-card-title>

                        <div class="d-flex flex-wrap gap-1">
                            <a href="#" class="btn btn-success bg-gradient"><i class="ti ti-plus me-1"></i> Add Payment</a>
                            <a href="#" class="btn btn-secondary bg-gradient"><i class="ti ti-file-import me-1"></i> Import</a>
                        </div>
                    </b-card-header>

                    <b-table-simple responsive hover class="mb-0 text-nowrap">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>Bill No</b-th>
                                <b-th>Patient Name</b-th>
                                <b-th>Doctor Name</b-th>
                                <b-th>Insurance Company</b-th>
                                <b-th>Payment</b-th>
                                <b-th>Bill Date</b-th>
                                <b-th>Charge</b-th>
                                <b-th>Tax</b-th>
                                <b-th>Discount</b-th>
                                <b-th class="pe-3 text-end">Total</b-th>
                            </b-tr>
                        </b-thead>

                        <b-tbody>
                            <b-tr v-for="(item, idx) in paymentList" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>{{ item.billNo }}</b-td>
                                <b-td>
                                    <a href="#">{{ item.patient }}</a>
                                </b-td>
                                <b-td>{{ item.doctor }}</b-td>
                                <b-td>{{ item.insuranceCompany }}</b-td>
                                <b-td>{{ toSentenceCase(item.payment) }}</b-td>
                                <b-td>{{ item.billDate }}</b-td>
                                <b-td>{{ currency }}{{ item.charge }}</b-td>
                                <b-td>{{ item.tax }}%</b-td>
                                <b-td>{{ item.discount }}%</b-td>
                                <b-td class="fw-semibold pe-3 text-end">{{ currency }}{{ item.total }}</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>

                    <b-card-footer>
                        <b-pagination v-model="currentPage" :total-rows="30" :per-page="10" class="justify-content-end mb-0" />
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import { currency } from '@/helpers';
import { toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import StatisticCard from '@/pages/hospital/payments/components/StatisticCard.vue';
import { paymentList, statistics } from '@/pages/hospital/payments/components/data';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
