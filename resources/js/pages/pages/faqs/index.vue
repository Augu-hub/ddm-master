<template>
    <VerticalLayout>
        <PageTitle title="FAQs" subtitle="Pages" />

        <b-row>
            <b-col lg="12">
                <b-card no-body>
                    <b-card-body>
                        <b-row class="justify-content-between g-3">
                            <b-col xl="3" lg="6">
                                <div>
                                    <div class="avatar-lg bg-primary d-flex align-items-center justify-content-center mb-3 rounded">
                                        <i class="ti ti-question-mark fs-32 text-white"></i>
                                    </div>
                                    <h3 class="mb-1">Frequently Asked Questions</h3>
                                    <p class="mb-3">Browse Through The Most Frequently Asked Questions</p>

                                    <div class="d-flex align-items-center mb-3 ms-0 gap-2 rounded border p-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <i class="ti ti-search fs-23 text-primary"></i>
                                        </div>
                                        <div>
                                            <a href="#!" class="fw-medium fs-15">Visit Help Center</a>
                                            <p class="mb-0 mt-1">Check Out Tutorials</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center mb-3 ms-0 gap-2 rounded border p-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <i class="ti ti-phone fs-23 text-primary"></i>
                                        </div>
                                        <div>
                                            <a href="#!" class="fw-medium fs-15">Book a Demo</a>
                                            <p class="mb-0 mt-1">1:1 Talk With A Tax Specialist.</p>
                                        </div>
                                    </div>
                                    <div class="d-flex align-items-center ms-0 gap-2 rounded border p-2">
                                        <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                            <i class="ti ti-brand-hipchat fs-23 text-primary"></i>
                                        </div>
                                        <div>
                                            <a href="#!" class="fw-medium fs-15">Live Chat Support</a>
                                            <p class="mb-0 mt-1">24/7 available. No chatbots.</p>
                                        </div>
                                    </div>
                                    <div class="mt-3">
                                        <a href="#!" class="btn btn-primary">Show More</a>
                                    </div>
                                </div>
                            </b-col>
                            <b-col xl="5" lg="6" class="text-center">
                                <img :src="faq" alt="" class="img-fluid mt-3" />
                                <div class="app-search app-search-lg mt-3 py-1">
                                    <input type="text" class="form-control rounded py-2" placeholder="Search..." />
                                    <i class="app-search-icon ti ti-search fs-18 text-muted"></i>
                                </div>
                                <div class="d-flex align-items-center mt-2 flex-wrap gap-1">
                                    <h5 class="mb-0">Popular Searched :</h5>
                                    <b-badge :variant="null" pill class="text-bg-light fw-normal fs-6 px-2 py-1">Apps </b-badge>
                                    <b-badge :variant="null" pill class="text-bg-light fw-normal fs-6 px-2 py-1"> Developers </b-badge>
                                    <b-badge :variant="null" pill class="text-bg-light fw-normal fs-6 px-2 py-1"> Repair </b-badge>
                                    <b-badge :variant="null" pill class="text-bg-light fw-normal fs-6 px-2 py-1"> Billing </b-badge>
                                    <b-badge :variant="null" pill class="text-bg-light fw-normal fs-6 px-2 py-1"> Payment </b-badge>
                                </div>
                            </b-col>
                            <b-col xl="3" lg="6">
                                <div>
                                    <b-card-title tag="h4" class="mb-3">Write Your Question :</b-card-title>
                                    <div class="bg-light-subtle rounded border p-3">
                                        <b-form>
                                            <b-form-group label="Your Name" class="mb-2">
                                                <b-form-input type="text" placeholder="Full Name" />
                                            </b-form-group>

                                            <b-form-group label="Email" class="mb-2">
                                                <b-form-input type="email" placeholder="Email" />
                                            </b-form-group>

                                            <b-form-group label="Phone number" class="mb-2">
                                                <b-form-input type="number" placeholder="Number" />
                                            </b-form-group>

                                            <b-form-group label="Enter Your Question">
                                                <b-form-textarea :rows="3" placeholder="Message" />
                                            </b-form-group>
                                        </b-form>
                                        <div class="mt-2">
                                            <a href="#!" class="btn btn-primary w-100">Send</a>
                                        </div>
                                    </div>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="3">
                <div class="bg-body-secondary docs-nav rounded shadow">
                    <ul class="nav flex-column fs-15 bg-transparent ps-2">
                        <li class="nav-item">
                            <a href="#general" class="nav-link">General</a>
                        </li>
                        <li class="nav-item">
                            <a href="#integration" class="nav-link">Integration</a>
                        </li>
                        <li class="nav-item">
                            <a href="#payment" class="nav-link">Payment</a>
                        </li>
                        <li class="nav-item">
                            <a href="#shipping" class="nav-link">Shipping</a>
                        </li>
                        <li class="nav-item">
                            <a href="#return" class="nav-link">Return</a>
                        </li>
                    </ul>
                </div>
            </b-col>
            <b-col xl="9">
                <b-card no-body>
                    <b-card-body>
                        <b-card-title tag="h4" class="anchor mb-3" id="general">General FAQ's</b-card-title>

                        <b-accordion>
                            <b-accordion-item v-for="(item, idx) in generalFAQs" :key="idx" :title="item.question" :visible="idx == 0">
                                {{ item.answer }}
                            </b-accordion-item>
                        </b-accordion>
                    </b-card-body>
                </b-card>
                <b-card no-body>
                    <b-card-body>
                        <b-card-title tag="h4" class="anchor mb-3" id="integration">Integration</b-card-title>

                        <b-accordion>
                            <b-accordion-item v-for="(item, idx) in integrationFAQs" :key="idx" :title="item.question" :visible="idx == 0">
                                {{ item.answer }}
                            </b-accordion-item>
                        </b-accordion>
                    </b-card-body>
                </b-card>
                <b-card no-body>
                    <b-card-body>
                        <b-card-title tag="h4" class="anchor mb-3" id="payment">Payment</b-card-title>

                        <b-accordion>
                            <b-accordion-item v-for="(item, idx) in paymentFAQs" :key="idx" :title="item.question" :visible="idx == 0">
                                {{ item.answer }}
                            </b-accordion-item>
                        </b-accordion>
                    </b-card-body>
                </b-card>
                <b-card no-body>
                    <b-card-body>
                        <b-card-title tag="h4" class="anchor mb-3" id="shipping">Shipping</b-card-title>

                        <b-accordion>
                            <b-accordion-item v-for="(item, idx) in shippingFAQs" :key="idx" :title="item.question" :visible="idx == 0">
                                {{ item.answer }}
                            </b-accordion-item>
                        </b-accordion>
                    </b-card-body>
                </b-card>
                <b-card no-body>
                    <b-card-body>
                        <b-card-title tag="h4" class="anchor mb-3" id="return">Return</b-card-title>

                        <b-accordion>
                            <b-accordion-item v-for="(item, idx) in returnFAQs" :key="idx" :title="item.question" :visible="idx == 0">
                                {{ item.answer }}
                            </b-accordion-item>
                        </b-accordion>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import faq from '@/images/png/faq.png';
import { generalFAQs, integrationFAQs, paymentFAQs, returnFAQs, shippingFAQs } from '@/pages/pages/faqs/components/data';
</script>
