<template>
    <VerticalLayout>
        <PageTitle title="Invoice Details" subtitle="Invoices" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-body>
                        <div class="d-flex align-items-start justify-content-between mb-4">
                            <div>
                                <img :src="logoDark" alt="dark logo" height="24" />
                            </div>
                            <div class="text-end">
                                <b-badge :variant="null" class="bg-success-subtle text-success fs-12 mb-3 px-1">Paid</b-badge>
                                <h3 class="fw-bolder fs-20 m-0">Invoice: #INV7896</h3>
                            </div>
                        </div>

                        <b-row>
                            <b-col lg="4">
                                <div class="mb-4">
                                    <h5 class="fw-bold fs-14 mb-2 pb-1">Invoice From :</h5>
                                    <h6 class="fs-14 mb-2">Chris Keller</h6>
                                    <h6 class="fs-14 text-muted lh-base mb-2">
                                        1055 Station Street Fremont, <br />
                                        CA - 94539
                                    </h6>
                                    <h6 class="fs-14 text-muted mb-0">Phone: 510-875-0005</h6>
                                </div>
                                <div>
                                    <h5 class="fw-bold fs-14">Invoice Date :</h5>
                                    <h6 class="fs-14 text-muted">12 Apr 2024</h6>
                                </div>
                            </b-col>

                            <b-col lg="4">
                                <div class="mb-4">
                                    <h5 class="fw-bold fs-14 mb-2 pb-1">Bill To :</h5>
                                    <h6 class="fs-14 mb-2">Lucian Obrien</h6>
                                    <h6 class="fs-14 text-muted lh-base mb-2">1147 Rohan Drive Suite, <br />Burlington, VT / 82021</h6>
                                    <h6 class="fs-14 text-muted mb-0">Phone: 904-966-2836</h6>
                                </div>
                                <div>
                                    <h5 class="fw-bold fs-14">Due Date :</h5>
                                    <h6 class="fs-14 text-muted">28 Apr 2024</h6>
                                </div>
                            </b-col>

                            <b-col lg="4" class="text-end">
                                <img :src="qrCode" alt="qr-code-image" height="100" />
                            </b-col>
                        </b-row>
                    </b-card-body>

                    <div class="mt-4">
                        <b-table-simple responsive class="table-nowrap mb-0 text-center align-middle">
                            <b-thead>
                                <b-tr class="bg-light bg-opacity-50">
                                    <b-th class="border-0" scope="col" style="width: 50px">#</b-th>
                                    <b-th class="border-0 text-start" scope="col">Product Details</b-th>
                                    <b-th class="border-0" scope="col">Quantity</b-th>
                                    <b-th class="border-0" scope="col">Unit price</b-th>
                                    <b-th class="border-0 text-end" scope="col">Amount</b-th>
                                </b-tr>
                            </b-thead>
                            <b-tbody id="products-list">
                                <b-tr>
                                    <b-th scope="row">01</b-th>
                                    <b-td class="text-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <Icon icon="logos:react" class="fs-22" />
                                            <div>
                                                <span class="fw-medium">React Development</span>
                                                <p class="text-muted mb-0">(Advance payment for 25 hours)</p>
                                            </div>
                                        </div>
                                    </b-td>
                                    <b-td>25</b-td>
                                    <b-td>{{ currency }}40.00</b-td>
                                    <b-td class="text-end">{{ currency }}1000.00</b-td>
                                </b-tr>
                                <b-tr>
                                    <b-th scope="row">02</b-th>
                                    <b-td class="text-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <Icon icon="logos:openai-icon" class="fs-22" />
                                            <div>
                                                <span class="fw-medium">OpenAI Support</span>
                                                <p class="text-muted mb-0">(Small help for bug fixes)</p>
                                            </div>
                                        </div>
                                    </b-td>
                                    <b-td>01</b-td>
                                    <b-td>{{ currency }}200.00</b-td>
                                    <b-td class="text-end">{{ currency }}200.00</b-td>
                                </b-tr>
                                <b-tr>
                                    <b-th scope="row">03</b-th>
                                    <b-td class="text-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <Icon icon="logos:apple-app-store" class="fs-22" />
                                            <div>
                                                <span class="fw-medium">Custom App Development - iOS</span>
                                                <p class="text-muted mb-0">(Phase 1 payment)</p>
                                            </div>
                                        </div>
                                    </b-td>
                                    <b-td>01</b-td>
                                    <b-td>{{ currency }}1200.00</b-td>
                                    <b-td class="text-end">{{ currency }}1200.00</b-td>
                                </b-tr>
                                <b-tr>
                                    <b-th scope="row">04</b-th>
                                    <b-td class="text-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <Icon icon="logos:digital-ocean-icon" class="fs-22" />
                                            <div>
                                                <span class="fw-medium">Digital Ocean Server Setup</span>
                                                <p class="text-muted mb-0">(Fix amount)</p>
                                            </div>
                                        </div>
                                    </b-td>
                                    <b-td>01</b-td>
                                    <b-td>{{ currency }}199.00</b-td>
                                    <b-td class="text-end">{{ currency }}199.00</b-td>
                                </b-tr>
                            </b-tbody>
                        </b-table-simple>
                        <div>
                            <b-table-simple class="table-nowrap mb-0 ms-auto align-middle" style="width: 335px">
                                <b-tbody>
                                    <b-tr>
                                        <b-td class="fw-medium">Subtotal</b-td>
                                        <b-td class="text-end">{{ currency }}2,599.00</b-td>
                                    </b-tr>
                                    <b-tr>
                                        <b-td class="fw-medium">Shipping <small class="text-muted">(Free)</small></b-td>
                                        <b-td class="text-end">-</b-td>
                                    </b-tr>
                                    <b-tr>
                                        <b-td class="fw-medium">Discount <small class="text-muted">(10%)</small></b-td>
                                        <b-td class="text-end">-{{ currency }}259.90</b-td>
                                    </b-tr>
                                    <b-tr>
                                        <b-td class="fw-medium">Tax <small class="text-muted">(5%)</small></b-td>
                                        <b-td class="text-end">{{ currency }}116.95</b-td>
                                    </b-tr>
                                    <b-tr class="border-top border-top-dashed fs-16">
                                        <b-td class="fw-bold">Total Amount</b-td>
                                        <b-td class="fw-bold text-end">{{ currency }}2456.05</b-td>
                                    </b-tr>
                                </b-tbody>
                            </b-table-simple>
                        </div>
                    </div>

                    <b-card-body>
                        <div class="bg-body rounded-2 mt-4 p-2">
                            <p class="mb-0">
                                <span class="fs-12 fw-bold text-uppercase">Note : </span>Payment for all accounts is due within 7 days from the date
                                of invoice receipt. Payments can be made via cheque, credit card, or direct online payment. Failure to settle the
                                account within 7 days will result in the agreed quoted fee, as noted above, being charged against the credit details
                                provided as confirmation of the work undertaken.
                            </p>
                        </div>

                        <div class="mt-4">
                            <p class="mb-2 pb-2">
                                <b>Congratulations on your recent purchase!</b> It has been our pleasure to serve you, and we hope we see you again
                                soon.
                            </p>

                            <div class="d-inline-block">
                                <img :src="signature" alt="signature" height="32" />
                                <h5 class="mb-0 mt-2">Authorized Sign</h5>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>

                <div class="d-print-none mb-5">
                    <div class="d-flex justify-content-center gap-2">
                        <a href="javascript:window.print()" class="btn btn-primary"><i class="ti ti-printer me-1"></i> Print</a>
                        <a href="javascript: void(0);" class="btn btn-secondary"><i class="ti ti-send me-1"></i> Send</a>
                        <a href="javascript: void(0);" class="btn btn-info"><i class="ti ti-download me-1"></i> Download</a>
                    </div>
                </div>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import logoDark from '@/images/logo-dark.png';
import qrCode from '@/images/png/qr-code.png';
import signature from '@/images/png/signature.png';

import { currency } from '@/helpers';
import { Icon } from '@iconify/vue';
</script>
