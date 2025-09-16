<template>
    <VerticalLayout>
        <PageTitle title="Products" subtitle="eCommerce" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="border-bottom border-light">
                        <div class="d-flex justify-content-between flex-wrap gap-2">
                            <div class="position-relative">
                                <input type="text" class="form-control ps-4" placeholder="Search Company" />
                                <i class="ti ti-search position-absolute translate-middle-y top-50 ms-2"></i>
                            </div>

                            <div>
                                <Link href="/ecommerce/products/add" class="btn btn-primary"><i class="ti ti-plus me-1"></i>Add Products </Link>
                            </div>
                        </div>
                    </b-card-header>

                    <b-table-simple responsive hover class="mb-0 text-nowrap">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>Product ID</b-th>
                                <b-th>Name</b-th>
                                <b-th>Description</b-th>
                                <b-th>Price</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Category</b-th>
                                <b-th>Status</b-th>
                                <b-th class="text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr v-for="(item, idx) in products" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>{{ item.id }}</b-td>
                                <b-td>
                                    <div class="d-flex justify-content-start align-items-center gap-3">
                                        <div class="avatar-md">
                                            <img :src="item.image" alt="Product-1" class="img-fluid rounded-2" />
                                        </div>
                                        {{ item.name }}
                                    </div>
                                </b-td>
                                <b-td>{{ item.description }}</b-td>
                                <b-td>{{ currency }}{{ item.price }}</b-td>
                                <b-td>{{ item.quantity }}</b-td>
                                <b-td>{{ item.category }}</b-td>
                                <b-td>
                                    <b-badge
                                        :variant="null"
                                        class="fs-12 p-1"
                                        :class="item.status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                                    >
                                        {{ toSentenceCase(item.status) }}
                                    </b-badge>
                                </b-td>
                                <b-td class="pe-3">
                                    <div class="hstack justify-content-end gap-1">
                                        <a href="javascript:void(0);" class="btn btn-soft-primary btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-eye"></i
                                        ></a>
                                        <a href="javascript:void(0);" class="btn btn-soft-success btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-edit fs-16"></i
                                        ></a>
                                        <a href="javascript:void(0);" class="btn btn-soft-danger btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-trash"></i
                                        ></a>
                                    </div>
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
import { products } from '@/pages/e-commerce/products/components/data';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
