<template>
    <VerticalLayout>
        <PageTitle title="List Groups" subtitle="Base UI" />
        <b-row>
            <b-col xl="4">
                <UICard title="Basic example">
                    <b-list-group>
                        <b-list-group-item><i class="ti ti-brand-google-drive fs-18 me-1 align-middle"></i> Google Drive</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-messenger fs-18 me-1 align-middle"></i> Facebook Messenger</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-apple fs-18 me-1 align-middle"></i> Apple Technology Company</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-intercom fs-18 me-1 align-middle"></i> Intercom Support System</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-paypal fs-18 me-1 align-middle"></i> Paypal Payment Gateway</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Active items">
                    <b-list-group>
                        <b-list-group-item active><i class="ti ti-brand-google-drive fs-18 me-1 align-middle"></i> Google Drive</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-messenger fs-18 me-1 align-middle"></i> Facebook Messenger</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-apple fs-18 me-1 align-middle"></i> Apple Technology Company</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-intercom fs-18 me-1 align-middle"></i> Intercom Support System</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-paypal fs-18 me-1 align-middle"></i> Paypal Payment Gateway</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Disabled items">
                    <b-list-group>
                        <b-list-group-item disabled><i class="ti ti-brand-google-drive fs-18 me-1 align-middle"></i> Google Drive</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-messenger fs-18 me-1 align-middle"></i> Facebook Messenger</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-apple fs-18 me-1 align-middle"></i> Apple Technology Company</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-intercom fs-18 me-1 align-middle"></i> Intercom Support System</b-list-group-item>
                        <b-list-group-item><i class="ti ti-brand-paypal fs-18 me-1 align-middle"></i> Paypal Payment Gateway</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="4">
                <UICard title="Links and Buttons">
                    <b-list-group>
                        <b-list-group-item tag="a" href="#" action active>Paypal Payment Gateway </b-list-group-item>
                        <b-list-group-item tag="a" href="#" action>Google Drive</b-list-group-item>
                        <b-list-group-item tag="button" type="button" action>Facebook Messenger</b-list-group-item>
                        <b-list-group-item tag="button" type="button" action>Apple Technology Company</b-list-group-item>
                        <b-list-group-item tag="a" href="#" class="list-group-item list-group-item-action disabled" tabindex="-1" aria-disabled="true"
                            >Intercom Support System</b-list-group-item
                        >
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Flush">
                    <b-list-group flush>
                        <b-list-group-item>Google Drive</b-list-group-item>
                        <b-list-group-item>Facebook Messenger</b-list-group-item>
                        <b-list-group-item>Apple Technology Company</b-list-group-item>
                        <b-list-group-item>Intercom Support System</b-list-group-item>
                        <b-list-group-item>Paypal Payment Gateway</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Horizontal">
                    <b-list-group horizontal class="list-group-horizontal mb-3">
                        <b-list-group-item>Google</b-list-group-item>
                        <b-list-group-item>Whatsapp</b-list-group-item>
                        <b-list-group-item>Facebook</b-list-group-item>
                    </b-list-group>

                    <b-list-group horizontal class="list-group-horizontal-sm mb-3">
                        <b-list-group-item>Apple</b-list-group-item>
                        <b-list-group-item>PayPal</b-list-group-item>
                        <b-list-group-item>Intercom</b-list-group-item>
                    </b-list-group>

                    <b-list-group horizontal class="list-group-horizontal-md">
                        <b-list-group-item>Google</b-list-group-item>
                        <b-list-group-item>Whatsapp</b-list-group-item>
                        <b-list-group-item>Facebook</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="4">
                <UICard title="Contextual classes">
                    <b-list-group>
                        <b-list-group-item>Dapibus ac facilisis in</b-list-group-item>
                        <b-list-group-item variant="primary">A simple primary list group item</b-list-group-item>
                        <b-list-group-item variant="secondary">A simple secondary list group item</b-list-group-item>
                        <b-list-group-item variant="success">A simple success list group item</b-list-group-item>
                        <b-list-group-item variant="danger">A simple danger list group item</b-list-group-item>
                        <b-list-group-item variant="warning">A simple warning list group item</b-list-group-item>
                        <b-list-group-item variant="info">A simple info list group item</b-list-group-item>
                        <b-list-group-item variant="light">A simple light list group item</b-list-group-item>
                        <b-list-group-item variant="dark">A simple dark list group item</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Contextual classes with Link">
                    <b-list-group>
                        <b-list-group-item href="#" action>Darius ac facilities in</b-list-group-item>
                        <b-list-group-item href="#" action variant="primary">A simple primary list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="secondary">A simple secondary list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="success">A simple success list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="danger">A simple danger list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="warning">A simple warning list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="info">A simple info list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="light">A simple light list group item</b-list-group-item>
                        <b-list-group-item href="#" action variant="dark">A simple dark list group item</b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Custom content">
                    <b-list-group>
                        <b-list-group-item href="#" active>
                            <div class="d-flex justify-content-between w-100">
                                <h5 class="mb-1">List group item heading</h5>
                                <small>3 days ago</small>
                            </div>
                            <p class="mb-1">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
                            <small>Donec id elit non mi porta.</small>
                        </b-list-group-item>
                        <b-list-group-item href="#">
                            <div class="d-flex justify-content-between w-100">
                                <h5 class="mb-1">List group item heading</h5>
                                <small class="text-muted">3 days ago</small>
                            </div>
                            <p class="mb-1">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
                            <small class="text-muted">Donec id elit non mi porta.</small>
                        </b-list-group-item>
                        <b-list-group-item href="#">
                            <div class="d-flex justify-content-between w-100">
                                <h5 class="mb-1">List group item heading</h5>
                                <small class="text-muted">3 days ago</small>
                            </div>
                            <p class="mb-1">Donec id elit non mi porta gravida at eget metus. Maecenas sed diam eget risus varius blandit.</p>
                            <small class="text-muted">Donec id elit non mi porta.</small>
                        </b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="4">
                <UICard title="With badges">
                    <b-list-group>
                        <b-list-group-item class="d-flex justify-content-between align-items-center">
                            Gmail Emails
                            <span class="badge bg-primary rounded-pill">14</span>
                        </b-list-group-item>
                        <b-list-group-item class="d-flex justify-content-between align-items-center">
                            Pending Payments
                            <span class="badge bg-success rounded-pill">2</span>
                        </b-list-group-item>
                        <b-list-group-item class="d-flex justify-content-between align-items-center">
                            Action Needed
                            <span class="badge bg-danger rounded-pill">99+</span>
                        </b-list-group-item>
                        <b-list-group-item class="d-flex justify-content-between align-items-center">
                            Payments Done
                            <span class="badge bg-success rounded-pill">20+</span>
                        </b-list-group-item>
                        <b-list-group-item class="d-flex justify-content-between align-items-center">
                            Pending Payments
                            <span class="badge bg-warning rounded-pill">12</span>
                        </b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Checkboxes and radios">
                    <b-list-group>
                        <b-list-group-item>
                            <input class="form-check-input me-1" type="checkbox" value="" id="firstCheckbox" />
                            <label class="form-check-label" for="firstCheckbox">First checkbox</label>
                        </b-list-group-item>
                        <b-list-group-item>
                            <input class="form-check-input me-1" type="checkbox" value="" id="secondCheckbox" />
                            <label class="form-check-label" for="secondCheckbox">Second checkbox</label>
                        </b-list-group-item>
                    </b-list-group>

                    <b-list-group class="mt-2">
                        <b-list-group-item>
                            <input class="form-check-input me-1" type="radio" name="listGroupRadio" value="" id="firstRadio" checked />
                            <label class="form-check-label" for="firstRadio">First radio</label>
                        </b-list-group-item>
                        <b-list-group-item>
                            <input class="form-check-input me-1" type="radio" name="listGroupRadio" value="" id="secondRadio" />
                            <label class="form-check-label" for="secondRadio">Second radio</label>
                        </b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>

            <b-col xl="4">
                <UICard title="Numbered">
                    <b-list-group class="list-group-numbered">
                        <b-list-group-item class="d-flex justify-content-between align-items-start">
                            <div class="me-auto ms-2">
                                <div class="fw-bold">Osen Admin</div>
                                Osen Admin
                            </div>
                            <span class="badge bg-primary rounded-pill">865</span>
                        </b-list-group-item>
                        <b-list-group-item class="d-flex justify-content-between align-items-start">
                            <div class="me-auto ms-2">
                                <div class="fw-bold">Osen React Admin</div>
                                Osen React Admin
                            </div>
                            <span class="badge bg-primary rounded-pill">140</span>
                        </b-list-group-item>
                        <b-list-group-item class="d-flex justify-content-between align-items-start">
                            <div class="me-auto ms-2">
                                <div class="fw-bold">Angular Version</div>
                                Angular Version
                            </div>
                            <span class="badge bg-primary rounded-pill">85</span>
                        </b-list-group-item>
                    </b-list-group>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
