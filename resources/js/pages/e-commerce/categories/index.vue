<template>
    <VerticalLayout>
        <PageTitle title="Categories" subtitle="eCommerce" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="border-bottom border-light">
                        <b-row class="justify-content-between gy-2 position-relative">
                            <b-col lg="3">
                                <div class="position-relative">
                                    <input type="text" class="form-control ps-4" placeholder="Search Company" />
                                    <i class="ti ti-search position-absolute translate-middle-y top-50 ms-2"></i>
                                </div>
                            </b-col>

                            <b-col sm="6" xl="4" xxl="3">
                                <b-form>
                                    <div class="d-flex flex-lg-nowrap flex-wrap gap-2">
                                        <div class="flex-grow-1">
                                            <ChoicesSelect id="select" class="my-md-0 me-sm-3 my-1">
                                                <option>10</option>
                                                <option>20</option>
                                                <option>25</option>
                                                <option>30</option>
                                                <option>50</option>
                                            </ChoicesSelect>
                                        </div>
                                        <b-button variant="primary"><i class="ti ti-plus me-1"></i>Add Category</b-button>
                                    </div>
                                </b-form>
                            </b-col>
                        </b-row>
                    </b-card-header>

                    <b-table-simple responsive hover class="mb-0 text-nowrap">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>Categories</b-th>
                                <b-th>Average Price Range</b-th>
                                <b-th>Best Selling Items</b-th>
                                <b-th>Customer Rating (1-5)</b-th>
                                <b-th>Discounts Available</b-th>
                                <b-th>Status</b-th>
                                <b-th class="text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr v-for="(item, idx) in categories" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>
                                    <div class="d-flex justify-content-start align-items-center gap-3">
                                        <div class="avatar-md">
                                            <img :src="item.image" alt="Product-1" class="img-fluid rounded-2" />
                                        </div>
                                        <div class="d-flex flex-column">
                                            <span class="fw-medium text-nowrap">{{ item.name }}</span>
                                            <span class="text-dark fw-semibold"
                                                >Brand :
                                                <span v-for="(brand, i) in item.brands" class="fw-normal"
                                                    >{{ brand.name }}
                                                    <span v-if="i != item.brands.length - 1">, </span>
                                                </span>
                                            </span>
                                        </div>
                                    </div>
                                </b-td>
                                <b-td>{{ currency }}{{ item.priceRange.min }} - {{ currency }}{{ item.priceRange.max }}</b-td>
                                <b-td>
                                    <span v-for="(product, index) in item.bestSellingItems">
                                        {{ product }}
                                        <span v-if="index != item.bestSellingItems.length - 1">, </span>
                                    </span>
                                </b-td>
                                <b-td>
                                    <b-badge variant="light" class="text-dark fs-12 me-1 p-1"
                                        ><i class="ti ti-star-filled fs-14 text-warning me-1 align-text-top"></i> {{ item.rating }}
                                    </b-badge>
                                </b-td>
                                <b-td>{{ item.discountAvailable }}</b-td>
                                <b-td>
                                    <b-badge
                                        :variant="null"
                                        :class="item.status === 'active' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger'"
                                        class="fs-12 p-1"
                                        >{{ toSentenceCase(item.status) }}
                                    </b-badge>
                                </b-td>
                                <b-td class="pe-3">
                                    <div class="hstack justify-content-end gap-1">
                                        <a href="javascript:void(0);" class="btn btn-soft-primary btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-eye"></i
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
import ChoicesSelect from '@/components/ChoicesSelect.vue';
import PageTitle from '@/components/PageTitle.vue';
import { currency } from '@/helpers';
import { toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { categories } from '@/pages/e-commerce/categories/components/data';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
