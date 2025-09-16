<template>
    <VerticalLayout>
        <PageTitle title="Orders" subtitle="eCommerce" />

        <b-row>
            <b-col cols="12">
                <b-card>
                    <b-card-header class="border-bottom border-light">
                        <b-row class="justify-content-between g-3">
                            <b-col lg="6">
                                <b-row class="g-3">
                                    <b-col lg="3">
                                        <div class="position-relative">
                                            <input type="text" class="form-control ps-4" placeholder="Search Order" />
                                            <i class="ti ti-search position-absolute translate-middle-y top-50 ms-2"></i>
                                        </div>
                                    </b-col>
                                    <b-col lg="5">
                                        <div class="d-flex align-items-center flex-grow-1">
                                            <label for="status-select" class="me-2">Status</label>

                                            <div class="flex-grow-1">
                                                <ChoicesSelect id="select" class="my-md-0 my-1">
                                                    <option value="All">All</option>
                                                    <option value="Cancelled">Cancelled</option>
                                                    <option value="Completed">Completed</option>
                                                    <option value="Denied">Denied</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="Processing">Processing</option>
                                                    <option value="Refunded">Refunded</option>
                                                </ChoicesSelect>
                                            </div>
                                        </div>
                                    </b-col>
                                    <b-col lg="4">
                                        <b-input-group>
                                            <FlatPicker id="date1" v-model="date" :options="{ dateFormat: 'd M', mode: 'range' }" />
                                            <b-input-group-text class="bg-primary border-primary text-white">
                                                <i class="ti ti-calendar fs-15"></i>
                                            </b-input-group-text>
                                        </b-input-group>
                                    </b-col>
                                </b-row>
                            </b-col>

                            <b-col lg="6">
                                <div class="text-md-end mt-md-0 mt-3">
                                    <b-button variant="success" class="waves-effect waves-light me-1"
                                        ><i class="ti ti-settings me-1"></i>More Setting
                                    </b-button>
                                    <b-button variant="dark" class="waves-effect waves-light"
                                        ><i class="ti ti-filter me-1"></i>
                                        Filters
                                    </b-button>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card-header>

                    <b-table-simple responsive class="table-nowrap mb-0">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>Order ID</b-th>
                                <b-th>Customer Name</b-th>
                                <b-th>Product</b-th>
                                <b-th>Quantity</b-th>
                                <b-th>Total</b-th>
                                <b-th>Order Date</b-th>
                                <b-th>Payment Status</b-th>
                                <b-th>Order Status</b-th>
                                <b-th class="text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr v-for="(item, idx) in orders" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>
                                    <Link :href="item.url" class="text-muted fw-medium">#{{ item.id }}</Link>
                                </b-td>
                                <b-td>
                                    <h5 class="text-dark mb-0">
                                        <img :src="item.customer.image" alt="" class="rounded-circle avatar-xs me-1" />
                                        <a href="#!" class="text-dark">{{ item.customer.name }}</a>
                                    </h5>
                                </b-td>
                                <b-td>
                                    <p
                                        v-for="(prod, index) in item.products"
                                        :key="index"
                                        :class="index != item.products.length - 1 ? 'mb-1' : 'mb-0'"
                                    >
                                        <span class="text-dark fw-semibold">P{{ index + 1 }} -</span>
                                        {{ prod.name }}
                                    </p>
                                </b-td>
                                <b-td>
                                    <p
                                        v-for="(prod, index) in item.products"
                                        :key="index"
                                        :class="index != item.products.length - 1 ? 'mb-1' : 'mb-0'"
                                    >
                                        {{ prod.quantity }} Piece
                                    </p>
                                </b-td>

                                <b-td> {{ currency }}{{ item.total }} </b-td>
                                <b-td>
                                    {{ item.date }}
                                </b-td>
                                <b-td>
                                    <h5 class="mb-0">
                                        <b-badge
                                            :variant="null"
                                            :class="
                                                item.paymentStatus === 'completed'
                                                    ? 'text-success border-success-subtle'
                                                    : item.paymentStatus === 'pending'
                                                      ? 'text-warning border-warning-subtle'
                                                      : 'text-danger border-danger-subtle'
                                            "
                                            class="fs-11 border p-1"
                                            >{{ toSentenceCase(item.paymentStatus) }}
                                        </b-badge>
                                    </h5>
                                </b-td>
                                <b-td>
                                    <h5 class="mb-0">
                                        <b-badge
                                            :variant="null"
                                            :class="
                                                item.status === 'delivered'
                                                    ? 'badge-soft-info'
                                                    : item.status === 'dispatched'
                                                      ? 'badge-soft-warning'
                                                      : item.status === 'ready-to-pick'
                                                        ? 'badge-soft-dark'
                                                        : 'badge-soft-danger'
                                            "
                                            class="fs-11 p-1"
                                            ><i class="ti ti-checks me-1 align-middle"></i>{{ kebabToTitleCase(item.status) }}
                                        </b-badge>
                                    </h5>
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

                    <b-card-footer class="d-flex align-items-center justify-content-end">
                        <b-pagination v-model="currentPage" :total-rows="30" :per-page="10" class="justify-content-end mb-0" />
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ChoicesSelect from '@/components/ChoicesSelect.vue';
import FlatPicker from '@/components/FlatPicker.vue';
import PageTitle from '@/components/PageTitle.vue';
import { currency } from '@/helpers';
import { kebabToTitleCase, toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { orders } from '@/pages/e-commerce/orders/components/data';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const currentPage = ref(1);
const date = ref('01 May to 15 May');
</script>
