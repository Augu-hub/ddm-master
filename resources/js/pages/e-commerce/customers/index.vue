<template>
    <VerticalLayout>
        <PageTitle title="Customers" subtitle="eCommerce" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-light">
                        <b-card-title tag="h4">Manage Customers</b-card-title>
                        <a href="#" class="btn btn-secondary"><i class="ti ti-file-import me-1"></i> Import</a>
                    </b-card-header>

                    <b-table-simple responsive class="table-nowrap mb-0">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>Customer</b-th>
                                <b-th>Invoice</b-th>
                                <b-th>Status</b-th>
                                <b-th>Total Amount</b-th>
                                <b-th>Amount Due</b-th>
                                <b-th>Shop Rate</b-th>
                                <b-th>Due Date</b-th>
                                <b-th class="pe-3 text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>

                        <b-tbody>
                            <b-tr v-for="(item, idx) in customers" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>
                                    <h5 class="text-dark mb-0">
                                        <img :src="item.image" alt="" class="rounded-circle avatar-sm me-1" />
                                        <a href="#!" class="text-dark">{{ item.name }}</a>
                                    </h5>
                                </b-td>
                                <b-td>
                                    {{ item.invoice }}
                                </b-td>
                                <b-td>
                                    <h5 class="mb-0">
                                        <b-badge
                                            :variant="null"
                                            class="px-2 py-1"
                                            :class="item.status === 'active' ? 'badge-soft-success' : 'badge-soft-danger'"
                                        >
                                            {{ toSentenceCase(item.status) }}
                                        </b-badge>
                                    </h5>
                                </b-td>
                                <b-td> {{ currency }}{{ item.totalAmount }} </b-td>
                                <b-td> {{ currency }}{{ item.amountDue }} </b-td>
                                <b-td>
                                    <b-progress :value="item.shopRate" variant="warning" striped animated height="10px" />
                                </b-td>
                                <b-td>
                                    {{ item.dueDate }}
                                </b-td>
                                <b-td class="hstack justify-content-end gap-1 pe-3">
                                    <a href="javascript:void(0);" class="btn btn-soft-primary btn-icon btn-sm rounded-circle">
                                        <i class="ti ti-eye"></i
                                    ></a>
                                    <a href="javascript:void(0);" class="btn btn-soft-danger btn-icon btn-sm rounded-circle">
                                        <i class="ti ti-trash"></i
                                    ></a>
                                </b-td>
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
import { customers } from '@/pages/e-commerce/customers/components/data';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
