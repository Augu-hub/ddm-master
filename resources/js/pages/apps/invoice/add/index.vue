<template>
    <VerticalLayout>
        <PageTitle title="Add Invoice" subtitle="Invoices" />

        <b-row>
            <b-col cols="12">
                <b-card no-body class="position-relative">
                    <b-form>
                        <b-card-body>
                            <div class="d-flex align-items-start justify-content-between mb-4">
                                <div
                                    class="position-relative d-flex align-items-center justify-content-start overflow-hidden rounded border px-2"
                                    style="height: 60px; width: 260px"
                                >
                                    <label for="imageInput" class="position-absolute bottom-0 end-0 start-0 top-0"></label>
                                    <input class="d-none" type="file" id="imageInput" />
                                    <img id="preview" :src="logoDark" alt="Preview Image" height="28" />
                                </div>

                                <div class="text-end">
                                    <b-row class="g-1 align-items-center">
                                        <div class="col-auto">
                                            <label for="invoiceNo" class="col-form-label fs-16 fw-bold">#INV</label>
                                        </div>
                                        <div class="col-auto">
                                            <b-form-input type="number" id="invoiceNo" placeholder="00001234" />
                                        </div>
                                    </b-row>
                                </div>
                            </div>

                            <b-row>
                                <b-col xl="4" lg="4" md="6" sm="6" class="mt-sm-0 mt-3">
                                    <div class="mb-2">
                                        <FlatPicker
                                            id="invoice-date"
                                            label="Invoice Date :"
                                            placeholder="Select Date"
                                            :options="{ dateFormat: 'd M, Y', DefaultDate: 'today' }"
                                        />
                                    </div>
                                    <div class="mb-2">
                                        <FlatPicker
                                            id="due-date"
                                            label="Due Date :"
                                            placeholder="Select Date"
                                            :options="{ dateFormat: 'F j, Y', DefaultDate: 'today' }"
                                        />
                                    </div>

                                    <b-form-group label="Payment Status" class="mb-2">
                                        <b-form-select :options="paymentStatusOptions" />
                                    </b-form-group>

                                    <b-form-group label="Payment Method" class="mb-2">
                                        <b-form-select :options="paymentMethodOptions" />
                                    </b-form-group>
                                </b-col>
                                <b-col lg="4" md="6" sm="6">
                                    <div class="mb-4">
                                        <label class="form-label">Billing Address :</label>
                                        <div class="mb-2 pb-1">
                                            <b-form-input type="text" id="BName" placeholder="Full Name" />
                                        </div>
                                        <div class="mb-2 pb-1">
                                            <b-form-textarea id="BAddress" :rows="3" placeholder="Address" />
                                        </div>
                                        <div>
                                            <b-form-input type="number" id="BNumber" placeholder="Phone Number" />
                                        </div>
                                    </div>
                                </b-col>
                                <b-col xl="4" lg="4" md="6" sm="6" class="mt-sm-0 mt-3">
                                    <div class="mb-4">
                                        <label class="form-label">Shipping Address :</label>
                                        <div class="mb-2 pb-1">
                                            <b-form-input type="text" id="SName" placeholder="Full Name" />
                                        </div>
                                        <div class="mb-2 pb-1">
                                            <b-form-textarea id="SAddress" rows="3" placeholder="Address" />
                                        </div>
                                        <div>
                                            <b-form-input type="number" id="SNumber" placeholder="Phone Number" />
                                        </div>
                                    </div>
                                </b-col>
                            </b-row>

                            <div class="mt-4">
                                <div class="table-responsive">
                                    <b-table-simple class="table-nowrap mb-0 text-center align-middle">
                                        <b-thead>
                                            <b-tr class="bg-light bg-opacity-50">
                                                <b-th scope="col" class="border-0" style="width: 70px">#</b-th>
                                                <b-th scope="col" class="border-0 text-start">Product Details</b-th>
                                                <b-th scope="col" class="border-0" style="width: 140px">Quantity</b-th>
                                                <b-th scope="col" class="border-0" style="width: 140px">Unit price </b-th>
                                                <b-th scope="col" class="border-0" style="width: 240px">Amount</b-th>
                                                <b-th scope="col" class="border-0" style="width: 50px">•••</b-th>
                                            </b-tr>
                                        </b-thead>
                                        <b-tbody>
                                            <b-tr>
                                                <b-th scope="row">01</b-th>
                                                <b-td class="text-start">
                                                    <b-form-input type="text" id="product-detail-one" class="mb-1" placeholder="Product One" />
                                                    <b-form-input type="text" id="product-desc-one" placeholder="Item description" />
                                                </b-td>
                                                <b-td>
                                                    <b-form-input type="number" id="product-category-one" placeholder="Quantity" />
                                                </b-td>
                                                <b-td>
                                                    <b-form-input type="number" placeholder="Price" />
                                                </b-td>
                                                <b-td>
                                                    <b-form-input type="number" class="w-auto" placeholder="$0.00" />
                                                </b-td>
                                                <b-td>
                                                    <button type="button" class="btn rounded-circle btn-icon btn-ghost-danger flex-shrink-0">
                                                        <Icon icon="solar:trash-bin-trash-bold-duotone" class="fs-20" />
                                                    </button>
                                                </b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-th scope="row">02</b-th>
                                                <b-td class="text-start">
                                                    <b-form-input type="text" id="product-detail-two" class="mb-1" placeholder="Product Two" />
                                                    <b-form-input type="text" id="product-desc-two" placeholder="Item description" />
                                                </b-td>
                                                <b-td>
                                                    <b-form-input type="number" id="product-category-two" placeholder="Quantity" />
                                                </b-td>
                                                <b-td>
                                                    <b-form-input type="number" placeholder="Price" />
                                                </b-td>
                                                <b-td>
                                                    <b-form-input type="number" class="w-auto" placeholder="$0.00" />
                                                </b-td>
                                                <b-td>
                                                    <button type="button" class="btn rounded-circle btn-icon btn-ghost-danger flex-shrink-0">
                                                        <Icon icon="solar:trash-bin-trash-bold-duotone" class="fs-20" />
                                                    </button>
                                                </b-td>
                                            </b-tr>
                                        </b-tbody>
                                    </b-table-simple>

                                    <div class="p-2">
                                        <b-button variant="primary"><i class="ti ti-circle-plus me-1"></i> Add Products </b-button>
                                    </div>
                                </div>
                                <div>
                                    <b-table-simple borderless size="sm" class="table-nowrap mb-0 ms-auto align-middle" style="width: 300px">
                                        <b-tbody>
                                            <b-tr>
                                                <b-td class="fw-medium">Subtotal</b-td>
                                                <b-td class="text-end">
                                                    <div class="ms-auto" style="width: 160px">
                                                        <b-form-input type="number" id="productSubtotal" placeholder="$0.00" />
                                                    </div>
                                                </b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="fw-medium">Shipping</b-td>
                                                <b-td class="text-end">
                                                    <div class="ms-auto" style="width: 160px">
                                                        <b-form-input type="number" id="productShipping" placeholder="$0.00" />
                                                    </div>
                                                </b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="fw-medium">Discount <small class="text-muted">(10%)</small> </b-td>
                                                <b-td class="text-end">
                                                    <div class="ms-auto" style="width: 160px">
                                                        <b-form-input type="number" id="productDiscount" placeholder="$0.00" />
                                                    </div>
                                                </b-td>
                                            </b-tr>
                                            <b-tr>
                                                <b-td class="fw-medium">Tax <small class="text-muted">(18%)</small> </b-td>
                                                <b-td class="text-end">
                                                    <div class="ms-auto" style="width: 160px">
                                                        <b-form-input type="number" id="productTaxes" placeholder="$0.00" />
                                                    </div>
                                                </b-td>
                                            </b-tr>
                                            <b-tr class="fs-15">
                                                <b-th scope="row" class="fw-bold">Total Amount</b-th>
                                                <b-th class="text-end">
                                                    <div class="ms-auto" style="width: 160px">
                                                        <b-form-input type="number" id="productTotalAmount" disabled placeholder="$0.00" />
                                                    </div>
                                                </b-th>
                                            </b-tr>
                                        </b-tbody>
                                    </b-table-simple>
                                </div>
                            </div>

                            <div>
                                <b-form-group label="Note :">
                                    <b-form-textarea :rows="3" placeholder="Thanks for your business" />
                                </b-form-group>
                            </div>
                        </b-card-body>
                    </b-form>
                </b-card>
                <div class="mb-5">
                    <div class="d-flex justify-content-center gap-2">
                        <a href="javascript:window.print()" class="btn btn-primary gap-1"><i class="ti ti-eye fs-16"></i> Preview</a>
                        <a href="javascript: void(0);" class="btn btn-success gap-1"><i class="ti ti-device-floppy fs-16"></i> Save</a>
                        <a href="javascript: void(0);" class="btn btn-info gap-1"><i class="ti ti-send fs-16"></i> Send Invoice</a>
                    </div>
                </div>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import FlatPicker from '@/components/FlatPicker.vue';
import logoDark from '@/images/logo-dark.png';
import { Icon } from '@iconify/vue';

const paymentStatusOptions = [
    { value: '', text: 'Select Status' },
    { value: 'Paid', text: 'Paid' },
    { value: 'Unpaid', text: 'Unpaid' },
    { value: 'Cancelled', text: 'Cancelled' },
    { value: 'Refunded', text: 'Refunded' },
];

const paymentMethodOptions = [
    { value: '', text: 'Select Status' },
    { value: '1', text: 'Credit / Debit Card' },
    { value: '2', text: 'Bank Transfer' },
    { value: '3', text: 'PayPal' },
    { value: '4', text: 'Payoneer' },
    { value: '5', text: 'Cash On Delivery' },
    { value: '6', text: 'Wallet' },
    { value: '7', text: 'UPI (Gpay)' },
];
</script>
