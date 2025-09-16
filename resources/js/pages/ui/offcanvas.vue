<template>
    <VerticalLayout>
        <PageTitle title="Offcanvas" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Offcanvas">
                    <div class="d-flex flex-wrap gap-2">
                        <a class="btn btn-primary" href="#" role="button" @click="defaultOffcanvas = !defaultOffcanvas"> Link with href </a>
                        <b-button variant="primary" type="button" @click="defaultOffcanvas = !defaultOffcanvas">
                            Button with data-bs-target
                        </b-button>
                    </div>
                </UICard>

                <UICard title="Offcanvas Backdrop">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button variant="primary" class="mt-md-0 mt-2" type="button" @click="offcanvasScrolling = !offcanvasScrolling"
                            >Enable body scrolling</b-button
                        >
                        <b-button variant="primary" class="mt-md-0 mt-2" type="button" @click="offcanvasBackdrop = !offcanvasBackdrop"
                            >Enable backdrop (default)</b-button
                        >
                        <b-button
                            variant="primary"
                            class="mt-md-0 mt-2"
                            type="button"
                            @click="offcanvasScrollingBackdrop = !offcanvasScrollingBackdrop"
                            >Enable both scrolling & backdrop</b-button
                        >
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Offcanvas Placement">
                    <div>
                        <div class="d-flex flex-wrap gap-2">
                            <b-button variant="primary" class="mt-md-0 mt-2" type="button" @click="offcanvasTop = !offcanvasTop"
                                >Toggle Top offcanvas</b-button
                            >
                            <b-button variant="primary" class="mt-md-0 mt-2" type="button" @click="offcanvasStart = !offcanvasStart"
                                >Toggle right offcanvas</b-button
                            >
                            <b-button variant="primary" class="mt-md-0 mt-2" type="button" @click="offcanvasBottom = !offcanvasBottom"
                                >Toggle bottom offcanvas</b-button
                            >
                            <b-button variant="primary" class="mt-lg-0 mt-2" type="button" @click="offcanvasEnd = !offcanvasEnd"
                                >Toggle Left offcanvas</b-button
                            >
                        </div>
                    </div>
                </UICard>

                <UICard title="Dark Offcanvas">
                    <div>
                        <b-button variant="primary" class="mt-md-0 mt-2" type="button" @click="offcanvasDark = !offcanvasDark"
                            >Dark offcanvas</b-button
                        >
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>

    <!-- Default offcanvas -->
    <b-offcanvas v-model="defaultOffcanvas" placement="start" id="offcanvasExample">
        <template #title>
            <h4 id="offcanvasExampleLabel">Offcanvas</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>

        <ul class="ps-3">
            <li>At vero eos et accusamus et iusto odio dignissimos</li>
            <li>Et harum quidem rerum facilis</li>
            <li>Temporibus autem quibusdam et aut officiis</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Top -->
    <b-offcanvas v-model="offcanvasTop" placement="top" id="offcanvasExample">
        <template #title>
            <h4 id="offcanvasExampleLabel">Offcanvas Top</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Right -->
    <b-offcanvas v-model="offcanvasStart" placement="end" id="offcanvasExample">
        <template #title>
            <h4 id="offcanvasExampleLabel">Offcanvas right</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Bottom -->
    <b-offcanvas v-model="offcanvasBottom" placement="bottom" id="offcanvasExample">
        <template #title>
            <h4 id="offcanvasExampleLabel">Offcanvas bottom</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Left -->
    <b-offcanvas v-model="offcanvasEnd" placement="start" id="offcanvasExample">
        <template #title>
            <h4 id="offcanvasExampleLabel">Offcanvas Left</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Scrolling -->
    <b-offcanvas v-model="offcanvasScrolling" placement="start" id="offcanvasExample" :backdrop="false" :bodyScrolling="true">
        <template #title>
            <h5 id="offcanvasExampleLabel">Colored with scrolling</h5>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>

        <ul class="ps-3">
            <li>At vero eos et accusamus et iusto odio dignissimos</li>
            <li>Et harum quidem rerum facilis</li>
            <li>Temporibus autem quibusdam et aut officiis</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Backdrop -->
    <b-offcanvas v-model="offcanvasBackdrop" placement="start" id="offcanvasExample">
        <template #title>
            <h4 id="offcanvasExampleLabel">Offcanvas with backdrop</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>

        <ul class="ps-3">
            <li>At vero eos et accusamus et iusto odio dignissimos</li>
            <li>Et harum quidem rerum facilis</li>
            <li>Temporibus autem quibusdam et aut officiis</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Scrolling & Backdrop -->
    <b-offcanvas v-model="offcanvasScrollingBackdrop" placement="start" id="offcanvasExample" :bodyScrolling="true">
        <template #title>
            <h4 id="offcanvasExampleLabel">Backdroped with scrolling</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>

        <ul class="ps-3">
            <li>At vero eos et accusamus et iusto odio dignissimos</li>
            <li>Et harum quidem rerum facilis</li>
            <li>Temporibus autem quibusdam et aut officiis</li>
        </ul>
    </b-offcanvas>

    <!-- Offcanvas Placement Dark -->
    <b-offcanvas class="text-bg-dark" v-model="offcanvasDark" placement="start" id="offcanvasDark" header-close-class="btn-close-white">
        <template #title>
            <h4 id="offcanvasExampleLabel">Dark Offcanvas</h4>
        </template>
        <div>Some text as placeholder. In real life you can have the elements you have chosen. Like, text, images, lists, etc.</div>
        <h5 class="mt-3">List</h5>
        <ul class="ps-3">
            <li>Nemo enim ipsam voluptatem quia aspernatur</li>
            <li>Neque porro quisquam est, qui dolorem</li>
            <li>Quis autem vel eum iure qui in ea</li>
        </ul>
    </b-offcanvas>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

const defaultOffcanvas = ref(false);
const offcanvasEnd = ref(false);
const offcanvasStart = ref(false);
const offcanvasTop = ref(false);
const offcanvasBottom = ref(false);
const offcanvasDark = ref(false);

const offcanvasScrolling = ref(false);
const offcanvasBackdrop = ref(false);
const offcanvasScrollingBackdrop = ref(false);
</script>
