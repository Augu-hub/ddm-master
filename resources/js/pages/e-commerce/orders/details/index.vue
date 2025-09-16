<template>
    <VerticalLayout>
        <PageTitle title="Order Details" subtitle="eCommerce" />

        <b-row>
            <b-col lg="12">
                <b-card no-body>
                    <b-card-body>
                        <b-row class="align-items-start justify-content-between">
                            <b-col lg="5">
                                <p class="text-dark fw-medium fs-15 d-flex align-items-center mb-2 gap-1">
                                    <Icon icon="solar:box-bold-duotone" class="text-danger" />
                                    Order
                                    <i class="ti ti-arrow-right"></i>
                                    <b-badge :variant="null" class="bg-light-subtle rounded-pill text-dark fs-12 border px-2 py-1">
                                        #OC3142-EN
                                    </b-badge>
                                </p>

                                <h3 class="text-dark fw-semibold mb-1">
                                    Order ID : #OC3142-EN
                                    <b-badge
                                        :variant="null"
                                        pill
                                        class="bg-warning-subtle text-warning border-warning fs-11 my-2 me-1 border px-2 py-1"
                                    >
                                        Shipping
                                    </b-badge>
                                    <b-badge
                                        :variant="null"
                                        pill
                                        class="bg-success-subtle rounded-pill text-success border-success fs-11 my-2 border px-2 py-1"
                                    >
                                        No Action Needed
                                    </b-badge>
                                </h3>

                                <div class="d-flex align-items-center flex-wrap gap-2">
                                    <p class="fs-15 mb-0">Order Date : 2 July 2024</p>
                                    <div>|</div>
                                    <div>
                                        <p class="fs-15 text-success fw-medium d-flex align-items-center mb-0 gap-1">
                                            <i class="ti ti-plane-tilt"></i>Estimated delivery: July 9, 2024
                                        </p>
                                    </div>
                                </div>
                            </b-col>

                            <b-col lg="4" class="text-end">
                                <div class="d-flex justify-content-end my-2 flex-wrap gap-2">
                                    <a href="#!" class="btn btn-soft-primary">Invoice</a>
                                    <a href="#!" class="btn btn-primary">Edit Details</a>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="3" lg="6">
                <b-card no-body>
                    <b-card-body>
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                <Icon icon="solar:delivery-bold-duotone" class="fs-32 text-primary" />
                            </div>
                            <a href="#!" class="btn btn-primary btn-sm">Track Order</a>
                        </div>
                        <div class="d-flex align-items-center justify-content-between mt-5 flex-wrap pt-1">
                            <div>
                                <p class="fs-15 fw-medium text-muted mb-0 mb-1">Estimated Arrival</p>
                                <p class="text-dark fw-semibold fs-16 mb-0">9 July 2024</p>
                            </div>
                            <div>
                                <p class="fs-15 fw-medium text-muted mb-0 mb-1">Tracker ID</p>
                                <p class="text-dark fw-semibold fs-16 mb-0">#TR73647</p>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col xl="3" lg="6">
                <PaymentCard />
            </b-col>

            <b-col xl="6" lg="12">
                <b-card no-body>
                    <b-card-body>
                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                            <Icon icon="solar:scooter-bold-duotone" class="fs-32 text-primary" />
                        </div>
                        <p class="text-dark fs-20 text-dark fw-medium my-3">Be patient, package on deliver!</p>
                        <div class="d-flex align-items-center justify-content-between mb-2 mt-3 flex-wrap gap-2">
                            <div>
                                <b-badge
                                    :variant="null"
                                    class="bg-light-subtle rounded-pill text-dark fs-13 fw-medium d-flex align-items-center gap-1 border px-2 py-1"
                                >
                                    <i class="ti ti-plane-tilt text-warning"></i> 613 Kuhl Avenue
                                </b-badge>
                            </div>
                            <i class="ti ti-arrow-narrow-right fs-18 text-muted"></i>
                            <div>
                                <b-badge
                                    :variant="null"
                                    class="bg-light-subtle rounded-pill text-dark fs-13 fw-medium d-flex align-items-center gap-1 border px-2 py-1"
                                >
                                    <Icon icon="solar:map-point-bold-duotone" class="fs-18 text-warning" />
                                    1890 Uitsig St Grahamstad USA
                                </b-badge>
                            </div>
                        </div>
                        <b-progress variant="warning" :value="90" striped animated height="10px" />
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="4" lg="6">
                <Timeline />
            </b-col>
            <b-col xl="4" lg="6">
                <ShipmentDetails />
            </b-col>
            <b-col xl="4" lg="12">
                <b-card no-body>
                    <b-card-body>
                        <div class="mapouter">
                            <div class="gmap_canvas">
                                <iframe
                                    class="gmap_iframe rounded"
                                    width="100%"
                                    style="height: 264px"
                                    frameborder="0"
                                    scrolling="no"
                                    marginheight="0"
                                    marginwidth="0"
                                    src="https://maps.google.com/maps?width=1980&amp;height=400&amp;hl=en&amp;q=University of Oxford&amp;t=&amp;z=14&amp;ie=UTF8&amp;iwloc=B&amp;output=embed"
                                ></iframe>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="8" lg="12">
                <b-card no-body>
                    <b-card-header class="border-bottom">
                        <b-card-title tag="h5" class="mb-0">Order Items</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-row class="g-3">
                            <b-col lg="6" v-for="(item, idx) in orderItems" :key="idx">
                                <div class="d-flex align-items-center gap-3 rounded border border-dashed p-2">
                                    <div class="bg-light avatar-xl d-flex align-items-center justify-content-center rounded">
                                        <img :src="item.image" alt="" class="avatar-xl" />
                                    </div>
                                    <div>
                                        <a href="#!" class="text-dark fw-medium fs-15">{{ item.name }}</a>
                                        <p class="text-muted fw-medium fs-14 my-1">
                                            <span class="text-dark">Price : </span> {{ currency }}{{ item.price }}
                                        </p>
                                        <p class="text-muted fw-medium fs-14 my-1">
                                            <span class="text-dark">Size : </span>
                                            {{ item.size }}
                                        </p>
                                    </div>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
            <b-col xl="4" lg="12">
                <b-card no-body>
                    <b-card-header class="border-bottom">
                        <b-card-title tag="h5" class="mb-0">Purchase Summary</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-table-simple responsive borderless class="mb-0">
                            <b-tbody>
                                <b-tr>
                                    <b-td class="px-0">
                                        <p class="fs-15 fw-medium mb-0">Sub Total :</p>
                                    </b-td>
                                    <b-td class="text-dark fs-14 fw-medium px-0 text-end">{{ currency }}1001.8</b-td>
                                </b-tr>
                                <b-tr>
                                    <b-td class="px-0">
                                        <p class="fs-15 fw-medium mb-0">Discount :</p>
                                    </b-td>
                                    <b-td class="text-dark fs-14 fw-medium px-0 text-end">-{{ currency }}120.00</b-td>
                                </b-tr>
                                <b-tr>
                                    <b-td class="px-0">
                                        <p class="fs-15 fw-medium mb-0">Delivery Charge :</p>
                                    </b-td>
                                    <b-td class="text-success fs-14 fw-medium px-0 text-end">Free</b-td>
                                </b-tr>
                                <b-tr>
                                    <b-td class="px-0">
                                        <p class="fs-15 fw-medium mb-0">Estimated Tax (18.5%) :</p>
                                    </b-td>
                                    <b-td class="text-dark fs-14 fw-medium px-0 text-end">{{ currency }}30.00</b-td>
                                </b-tr>
                            </b-tbody>
                        </b-table-simple>
                    </b-card-body>
                    <b-card-footer class="d-flex align-items-center justify-content-between border-top">
                        <div>
                            <p class="fw-medium text-dark fs-15 mb-0">Grand Amount</p>
                        </div>
                        <div>
                            <p class="fw-medium fs-14 text-dark mb-0">{{ currency }}911.8</p>
                        </div>
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>

        <div class="d-flex justify-content-end mb-3 gap-2 text-end">
            <a href="#!" class="btn btn-primary">Contact To Seller</a>
            <a href="#!" class="btn btn-outline-primary">Invoice</a>
        </div>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import { currency } from '@/helpers';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { orderItems } from '@/pages/e-commerce/orders/details/components/data';
import PaymentCard from '@/pages/e-commerce/orders/details/components/PaymentCard.vue';
import ShipmentDetails from '@/pages/e-commerce/orders/details/components/ShipmentDetails.vue';
import Timeline from '@/pages/e-commerce/orders/details/components/Timeline.vue';
import { Icon } from '@iconify/vue';
</script>
