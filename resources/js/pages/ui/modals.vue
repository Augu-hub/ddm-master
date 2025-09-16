<template>
    <VerticalLayout>
        <PageTitle title="Modals" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Bootstrap Modals">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Standard  modal -->
                        <b-button type="button" variant="primary" @click="standardModal = !standardModal">Standard Modal</b-button>
                        <!-- Large modal -->
                        <b-button type="button" variant="info" @click="largeModal = !largeModal">Large Modal</b-button>
                        <!-- Small modal -->
                        <b-button type="button" variant="success" @click="smallModal = !smallModal">Small Modal</b-button>
                        <!-- Full width modal -->
                        <b-button type="button" variant="primary" @click="fullModal = !fullModal">Full Width Modal</b-button>
                        <!-- Scrollable modal -->
                        <b-button type="button" variant="secondary" @click="scrollableModal = !scrollableModal">Scrollable Modal</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Modal with Pages">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Sign Up modal -->
                        <b-button type="button" variant="primary" @click="signUpModal = !signUpModal">Sign Up Modal</b-button>
                        <!-- Log In modal -->
                        <b-button type="button" variant="info" @click="loginInModal = !loginInModal">Log In Modal</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Modal based Alerts">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Success Alert modal -->
                        <b-button type="button" variant="success" @click="successAlertModal = !successAlertModal">Success Alert</b-button>
                        <!-- Info Alert modal -->
                        <b-button type="button" variant="info" @click="infoAlertModal = !infoAlertModal">Info Alert</b-button>
                        <!-- Warning Alert modal -->
                        <b-button type="button" variant="warning" @click="warningAlertModal = !warningAlertModal">Warning Alert</b-button>
                        <!-- Danger Alert modal -->
                        <b-button type="button" variant="danger" @click="dangerAlertModal = !dangerAlertModal">Danger Alert</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Modal Position">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Top modal -->
                        <b-button type="button" variant="secondary" @click="topModal = !topModal">Top Modal</b-button>
                        <!-- Bottom modal -->
                        <b-button type="button" variant="secondary" @click="bottomModal = !bottomModal">Bottom Modal</b-button>
                        <!-- Center modal -->
                        <b-button type="button" variant="secondary" @click="centerModal = !centerModal">Center Modal</b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Colored Header Modals">
                    <div class="d-flex flex-wrap gap-2">
                        <template v-for="(item, idx) in modalVariant" :key="idx">
                            <b-button type="button" :variant="item" @click="() => coloredHeaderfn(item)">{{ toSentenceCase(item) }} Header</b-button>
                        </template>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Filled Modals">
                    <div class="d-flex flex-wrap gap-2">
                        <template v-for="(item, idx) in modalVariant" :key="idx">
                            <b-button type="button" :variant="item" @click="() => filledfn(item)">{{ toSentenceCase(item) }} Header</b-button>
                        </template>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Multiple Modal">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Multiple modal -->
                        <b-button type="button" variant="primary" @click="multipleModal = !multipleModal">Multiple Modal</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Toggle Between Modals">
                    <b-button variant="secondary" @click="toggleModal1 = !toggleModal1">Open First Modal</b-button>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Fullscreen Modal">
                    <div class="hstack flex-wrap gap-2">
                        <b-button type="button" variant="primary" @click="fsModal = !fsModal">Fullscreen Modal</b-button>
                        <b-button type="button" variant="primary" @click="fsBSMModal = !fsBSMModal">Full Screen Below sm</b-button>
                        <b-button type="button" variant="primary" @click="fsBMDModal = !fsBMDModal">Full Screen Below md</b-button>
                        <b-button type="button" variant="primary" @click="fsBLGModal = !fsBLGModal">Full Screen Below lg</b-button>
                        <b-button type="button" variant="primary" @click="fsBXLModal = !fsBXLModal">Full Screen Below xl</b-button>
                        <b-button type="button" variant="primary" @click="fsBXXLModal = !fsBXXLModal">Full Screen Below xxl</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Static Backdrop">
                    <div class="d-flex flex-wrap gap-2">
                        <!-- Static Backdrop modal -->
                        <b-button type="button" variant="info" @click="staticBackdropModal = !staticBackdropModal"> Static Backdrop </b-button>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Varying Modal Content">
                    <div class="hstack flex-wrap gap-2">
                        <b-button type="button" variant="primary" @click="varyingModalModal = !varyingModalModal">Open modal for @mdo</b-button>
                        <b-button type="button" variant="primary" @click="varyingModalModal = !varyingModalModal">Open modal for @fat</b-button>
                        <b-button type="button" variant="primary" @click="varyingModalModal = !varyingModalModal"
                            >Open modal for @getbootstrap</b-button
                        >
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>

    <!-- Standard Modal -->
    <b-modal v-model="standardModal" title="Modal Heading" title-tag="h4" ok-title="Save changes" cancel-title="Close" cancel-variant="light">
        <h5>Text in a modal</h5>
        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
        <hr />
        <h5>Overflowing text to show scroll behavior</h5>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p class="mb-0">
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
    </b-modal>

    <!-- Large Modal -->
    <b-modal v-model="largeModal" title="Large modal" title-tag="h4" size="lg" hide-footer> ... </b-modal>

    <!-- Small Modal -->
    <b-modal v-model="smallModal" title="Small modal" title-tag="h4" size="sm" hide-footer> ... </b-modal>

    <!-- Full Width Modal -->
    <b-modal
        v-model="fullModal"
        title="Modal Heading"
        title-tag="h4"
        dialog-class="modal-full-width"
        ok-title="Save changes"
        cancel-title="Close"
        cancel-variant="light"
    >
        <h5>Text in a modal</h5>
        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
        <hr />
        <h5>Overflowing text to show scroll behavior</h5>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p class="mb-0">
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
    </b-modal>

    <!-- Scrollable Modal -->
    <b-modal
        v-model="scrollableModal"
        title="Modal title"
        title-tag="h4"
        ok-title="Save changes"
        cancel-title="Close"
        cancel-variant="secondary"
        scrollable
    >
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p>
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
        <p class="mb-0">
            Aenean lacinia bibendum nulla sed consectetur. Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Donec sed odio dui.
            Donec ullamcorper nulla non metus auctor fringilla.
        </p>
    </b-modal>

    <!-- Sign Up Modal -->
    <b-modal v-model="signUpModal" title="Small modal" title-tag="h4" hide-footer hide-header>
        <div class="auth-brand position-relative top-0 mb-4 mt-2 text-center">
            <a href="index.html" class="logo-dark">
                <span><img :src="logoDark" alt="dark logo" height="22" /></span>
            </a>
            <a href="index.html" class="logo-light">
                <span><img :src="logo" alt="logo" height="22" /></span>
            </a>
        </div>

        <form class="pe-3 ps-3" action="#">
            <div class="mb-3">
                <label for="username" class="form-label">Name</label>
                <input class="form-control" type="email" id="username" required placeholder="Michael Zenaty" />
            </div>

            <div class="mb-3">
                <label for="emailaddress" class="form-label">Email address</label>
                <input class="form-control" type="email" id="emailaddress" required placeholder="john@deo.com" />
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <input class="form-control" type="password" required id="password" placeholder="Enter your password" />
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input fs-15" id="customCheck1" />
                    <label class="form-check-label" for="customCheck1">I accept <a href="#">Terms and Conditions</a></label>
                </div>
            </div>

            <div class="mb-3 text-center">
                <b-button variant="primary" type="submit">Sign Up Free</b-button>
            </div>
        </form>
    </b-modal>

    <!-- Log In Modal -->
    <b-modal v-model="loginInModal" title="Small modal" title-tag="h4" hide-footer hide-header>
        <div class="auth-brand position-relative top-0 mb-4 mt-2 text-center">
            <a href="index.html" class="logo-dark">
                <span><img :src="logoDark" alt="dark logo" height="22" /></span>
            </a>
            <a href="index.html" class="logo-light">
                <span><img :src="logo" alt="logo" height="22" /></span>
            </a>
        </div>

        <form action="#" class="pe-3 ps-3">
            <div class="mb-3">
                <label for="emailaddress1" class="form-label">Email address</label>
                <input class="form-control" type="email" id="emailaddress1" required placeholder="john@deo.com" />
            </div>

            <div class="mb-3">
                <label for="password1" class="form-label">Password</label>
                <input class="form-control" type="password" required id="password1" placeholder="Enter your password" />
            </div>

            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="RememberCheckBox" />
                    <label class="form-check-label" for="RememberCheckBox">Remember me</label>
                </div>
            </div>

            <div class="mb-3 text-center">
                <b-button variant="primary" class="rounded-pill" type="submit">Sign In</b-button>
            </div>
        </form>
    </b-modal>

    <!-- Success Alert -->
    <b-modal v-model="successAlertModal" hide-header hide-footer size="sm" body-class="p-4" content-class="modal-filled bg-success">
        <div class="text-center">
            <i class="ti ti-check h1"></i>
            <h4 class="mt-2">Well Done!</h4>
            <p class="mt-3">
                Congratulations! You've achieved success! 🎉 Your hard work, dedication, and perseverance have paid off. Keep up the great work and
                continue to strive for excellence.
            </p>
            <b-button type="button" variant="light" class="my-2" data-bs-dismiss="modal">Continue</b-button>
        </div>
    </b-modal>

    <!-- Info Alert -->
    <b-modal v-model="infoAlertModal" hide-header hide-footer size="sm" body-class="p-4">
        <div class="text-center">
            <i class="ti ti-info-square-rounded h1 text-info"></i>
            <h4 class="mt-2">Heads up!</h4>
            <p class="mt-3">Please be informed that our platform will undergo scheduled maintenance on 21 April from 10:30 PM to 11:45 PM.</p>
            <b-button type="button" variant="info" class="my-2" data-bs-dismiss="modal">Continue</b-button>
        </div>
    </b-modal>

    <!-- Warning Alert -->
    <b-modal v-model="warningAlertModal" hide-header hide-footer size="sm" body-class="p-4">
        <div class="text-center">
            <i class="ti ti-alert-circle h1 text-warning"></i>
            <h4 class="mt-2">Incorrect Information</h4>
            <p class="mt-3">
                We have detected suspicious activity on our platform. As a precautionary measure, we urge all users to refrain from logging in or
                providing any personal information until further notice.
            </p>
            <b-button type="button" variant="warning" class="my-2" data-bs-dismiss="modal">Continue</b-button>
        </div>
    </b-modal>

    <!-- Danger Alert -->
    <b-modal v-model="dangerAlertModal" hide-header hide-footer size="sm" body-class="p-4" content-class="modal-filled bg-danger">
        <div class="text-center">
            <i class="ti ti-circle-x h1"></i>
            <h4 class="mt-2">Oh snap!</h4>
            <p class="mt-3">
                A critical security breach has been identified on our platform. Your personal information and sensitive data may be at risk.
            </p>
            <b-button type="button" variant="light" class="my-2" data-bs-dismiss="modal">Continue</b-button>
        </div>
    </b-modal>

    <!-- Top Modal -->
    <b-modal
        v-model="topModal"
        title="Modal Heading"
        title-tag="h4"
        ok-title="Save changes"
        cancel-title="Close"
        cancel-variant="light"
        dialog-class="modal-top"
    >
        <h5 class="mt-0">Text in a modal</h5>
        <p class="my-0">Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
    </b-modal>

    <!-- Bottom Modal -->
    <b-modal
        v-model="bottomModal"
        title="Modal Heading"
        title-tag="h4"
        ok-title="Save changes"
        cancel-title="Close"
        cancel-variant="light"
        dialog-class="modal-bottom"
    >
        <h5 class="mt-0">Text in a modal</h5>
        <p class="my-0">Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
    </b-modal>

    <!-- Center Modal -->
    <b-modal
        v-model="centerModal"
        title="Center Modal"
        title-tag="h4"
        ok-title="Save changes"
        cancel-title="Close"
        cancel-variant="light"
        hide-footer
        centered
    >
        <h5 class="mt-0">Overflowing text to show scroll behavior</h5>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p class="mb-0">
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
    </b-modal>

    <!-- Colored Header Modals -->
    <b-modal
        v-model="coloredHeaderModal"
        title="Modal Heading"
        title-tag="h4"
        :header-class="`text-bg-${coloredHeaderVariant}`"
        ok-title="Save changes"
        :ok-variant="coloredHeaderVariant"
        cancel-title="Close"
        cancel-variant="light"
        header-close-class="btn-close-white"
    >
        <h5 class="mt-0">{{ toSentenceCase(coloredHeaderVariant) }} Background</h5>
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p class="mb-0">
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
    </b-modal>

    <!-- Filled Modals -->
    <b-modal
        v-model="filledModal"
        :title="`${toSentenceCase(filledVariant)} Filled Modal`"
        title-tag="h4"
        :content-class="`modal-filled bg-${filledVariant}`"
        ok-title="Save changes"
        ok-variant="light"
        cancel-title="Close"
        :cancel-variant="filledVariant === 'danger' ? 'info' : 'danger'"
        header-close-class="btn-close-white"
    >
        <p>
            Cras mattis consectetur purus sit amet fermentum. Cras justo odio, dapibus ac facilisis in, egestas eget quam. Morbi leo risus, porta ac
            consectetur ac, vestibulum at eros.
        </p>
        <p>
            Praesent commodo cursus magna, vel scelerisque nisl consectetur et. Vivamus sagittis lacus vel augue laoreet rutrum faucibus dolor auctor.
        </p>
    </b-modal>

    <!-- Multiple Modal -->
    <b-modal v-model="multipleModal" title="Modal Heading" title-tag="h4" ok-title="Next" ok-only @ok="handleOk">
        <h5 class="mt-0">Text in a modal</h5>
        <p class="my-0">Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
    </b-modal>

    <b-modal v-model="multipleModal1" title="Modal Heading" title-tag="h4" ok-title="Close" ok-only>
        <h5>Text in a modal</h5>
        <p>Duis mollis, est non commodo luctus, nisi erat porttitor ligula.</p>
    </b-modal>

    <!-- Toggle Between Modals -->
    <b-modal v-model="toggleModal1" title="Modal 1" title-tag="h4" ok-title="Open second modal" ok-only @ok="handleToggles" centered>
        Show a second modal and hide this one with the button below.
    </b-modal>

    <b-modal v-model="toggleModal2" title="Modal 2" title-tag="h4" ok-title="Back To First" ok-only @ok="handleToggles" centered>
        Hide this modal and show the first with the button below.
    </b-modal>

    <!-- Fullscreen Modal -->
    <b-modal v-model="fsModal" title="Full Screen Modal" ok-title="Save Changes" cancel-title="Close" cancel-variant="light" fullscreen>
        ...
    </b-modal>
    <!-- Full Screen Below sm -->
    <b-modal v-model="fsBSMModal" title="Full screen below sm" ok-title="Save Changes" cancel-title="Close" cancel-variant="light" fullscreen="sm">
        ...
    </b-modal>
    <!-- Full Screen Below md -->
    <b-modal v-model="fsBMDModal" title="Full screen below md" ok-title="Save Changes" cancel-title="Close" cancel-variant="light" fullscreen="md">
        ...
    </b-modal>
    <!-- Full Screen Below lg -->
    <b-modal v-model="fsBLGModal" title="Full screen below lg" ok-title="Save Changes" cancel-title="Close" cancel-variant="light" fullscreen="lg">
        ...
    </b-modal>
    <!-- Full Screen Below xl -->
    <b-modal v-model="fsBXLModal" title="Full screen below xl" ok-title="Save Changes" cancel-title="Close" cancel-variant="light" fullscreen="xl">
        ...
    </b-modal>
    <!-- Full Screen Below xxl -->
    <b-modal v-model="fsBXXLModal" title="Full screen below xxl" ok-title="Save Changes" cancel-title="Close" cancel-variant="light" fullscreen="xxl">
        ...
    </b-modal>

    <!-- Static Backdrop -->
    <b-modal v-model="staticBackdropModal" title="Modal title" ok-title="Understood" cancel-title="Close" :noCloseOnBackdrop="true">
        I will not close if you click outside me. Don't even try to press escape key.
    </b-modal>

    <!-- Varying Modal Content -->
    <b-modal v-model="varyingModalModal" title="New message" ok-title="Send Message" cancel-title="Close">
        <form>
            <div class="mb-3">
                <label for="recipient-name" class="col-form-label">Recipient:</label>
                <input type="text" class="form-control" id="recipient-name" />
            </div>
            <div class="mb-3">
                <label for="message-text" class="col-form-label">Message:</label>
                <textarea class="form-control" id="message-text"></textarea>
            </div>
        </form>
    </b-modal>
</template>
<script setup lang="ts">
import { toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import logoDark from '@/images/logo-dark.png';
import logo from '@/images/logo.png';

const modalVariant = ['primary', 'success', 'info', 'warning', 'danger', 'dark'];

const standardModal = ref(false);
const largeModal = ref(false);
const smallModal = ref(false);
const fullModal = ref(false);
const scrollableModal = ref(false);

const signUpModal = ref(false);
const loginInModal = ref(false);

const successAlertModal = ref(false);
const infoAlertModal = ref(false);
const warningAlertModal = ref(false);
const dangerAlertModal = ref(false);

const topModal = ref(false);
const bottomModal = ref(false);
const centerModal = ref(false);

const coloredHeaderModal = ref(false);
const coloredHeaderVariant = ref('primary');

const coloredHeaderfn = (e: string) => {
    coloredHeaderVariant.value = e;
    coloredHeaderModal.value = true;
};

const filledModal = ref(false);
const filledVariant = ref('primary');

const filledfn = (e: string) => {
    filledVariant.value = e;
    filledModal.value = true;
};

const multipleModal = ref(false);
const multipleModal1 = ref(false);

const handleOk = () => {
    multipleModal.value = !multipleModal.value;
    multipleModal1.value = !multipleModal1.value;
};

const toggleModal1 = ref(false);
const toggleModal2 = ref(false);

const handleToggles = () => {
    toggleModal1.value = !toggleModal1.value;
    toggleModal2.value = !toggleModal2.value;
};

const fsModal = ref(false);
const fsBSMModal = ref(false);
const fsBMDModal = ref(false);
const fsBLGModal = ref(false);
const fsBXLModal = ref(false);
const fsBXXLModal = ref(false);

const staticBackdropModal = ref(false);
const varyingModalModal = ref(false);
</script>
