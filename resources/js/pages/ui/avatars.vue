<template>
    <VerticalLayout>
        <PageTitle title="Avatar" subtitle="Base UI" />
        <b-row>
            <b-col xxl="6">
                <UICard title="Sizing - Images">
                    <b-row>
                        <b-col md="3">
                            <img :src="avatar2" alt="img" class="img-fluid avatar-xs rounded" />
                            <p>
                                <code>.avatar-xs</code>
                            </p>
                            <img :src="avatar3" alt="img" class="img-fluid avatar-sm mt-2 rounded" />
                            <p class="mb-sm-0 mb-2">
                                <code>.avatar-sm</code>
                            </p>
                        </b-col>
                        <b-col md="3">
                            <img :src="avatar4" alt="img" class="img-fluid avatar-md rounded" />
                            <p>
                                <code>.avatar-md</code>
                            </p>
                        </b-col>
                        <b-col md="3">
                            <img :src="avatar5" alt="img" class="img-fluid avatar-lg rounded" />
                            <p>
                                <code>.avatar-lg</code>
                            </p>
                        </b-col>
                        <b-col md="3">
                            <img :src="avatar6" alt="img" class="img-fluid avatar-xl rounded" />
                            <p class="mb-0">
                                <code>.avatar-xl</code>
                            </p>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
            <b-col xxl="6">
                <UICard title="Sizing - Background Color">
                    <b-row>
                        <b-col md="3">
                            <div class="avatar-xs">
                                <span class="avatar-title bg-primary rounded"> xs </span>
                            </div>
                            <p class="mb-2 mt-1">Using <code>.avatar-xs</code></p>

                            <div class="avatar-sm mt-3">
                                <span class="avatar-title bg-success rounded"> sm </span>
                            </div>

                            <p class="mb-0 mt-1">Using <code>.avatar-sm</code></p>
                        </b-col>
                        <b-col md="3">
                            <div class="avatar-md">
                                <span class="avatar-title bg-danger-subtle text-danger fs-18 rounded"> MD </span>
                            </div>

                            <p class="mb-0 mt-1">Using <code>.avatar-md</code></p>
                        </b-col>
                        <b-col md="3">
                            <div class="avatar-lg">
                                <span class="avatar-title bg-info fs-22 rounded"> LG </span>
                            </div>

                            <p class="fs-14 mb-0 mt-1">Using <code>.avatar-lg</code></p>
                        </b-col>
                        <b-col md="3">
                            <div class="avatar-xl">
                                <span class="avatar-title bg-warning-subtle text-warning fs-24 rounded"> XL </span>
                            </div>

                            <p class="mb-0 mt-1">Using <code>.avatar-xl</code></p>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xxl="6">
                <UICard title="Rounded Circle">
                    <b-row>
                        <b-col md="4">
                            <img :src="avatar7" alt="img" class="img-fluid avatar-md rounded-circle" />
                            <p class="mt-1">
                                <code>.avatar-md .rounded-circle</code>
                            </p>
                        </b-col>

                        <b-col md="4">
                            <img :src="avatar8" alt="img" class="img-fluid avatar-lg rounded-circle" />
                            <p>
                                <code>.avatar-lg .rounded-circle</code>
                            </p>
                        </b-col>

                        <b-col md="4">
                            <img :src="avatar9" alt="img" class="img-fluid avatar-xl rounded-circle" />
                            <p class="mb-0">
                                <code>.avatar-xl .rounded-circle</code>
                            </p>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
            <b-col xxl="6">
                <UICard title="Rounded Circle Background">
                    <b-row>
                        <b-col md="4">
                            <div class="avatar-md">
                                <span class="avatar-title bg-secondary-subtle text-secondary fs-16 rounded-circle"> MD </span>
                            </div>

                            <p class="mb-0 mt-1">
                                <code>.avatar-md .rounded-circle</code>
                            </p>
                        </b-col>

                        <b-col md="4">
                            <div class="avatar-lg">
                                <span class="avatar-title bg-light text-dark fs-22 rounded-circle"> LG </span>
                            </div>

                            <p class="mb-0 mt-1">
                                <code>.avatar-lg .rounded-circle</code>
                            </p>
                        </b-col>

                        <b-col md="4">
                            <div class="avatar-xl">
                                <span class="avatar-title bg-primary-subtle text-primary fs-24 rounded-circle"> XL </span>
                            </div>

                            <p class="mb-0">
                                <code>.avatar-xl .rounded-circle</code>
                            </p>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xxl="12">
                <UICard title="Avatar Group">
                    <b-row>
                        <b-col xl="3">
                            <div class="avatar-group">
                                <div class="avatar">
                                    <img :src="avatar4" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div class="avatar">
                                    <img :src="avatar5" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div class="avatar">
                                    <img :src="avatar3" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div class="avatar">
                                    <img :src="avatar8" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div class="avatar">
                                    <img :src="avatar2" alt="" class="rounded-circle avatar-sm" />
                                </div>
                            </div>
                        </b-col>
                        <b-col xl="3">
                            <div class="avatar-group">
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-success rounded-circle fw-bold"> D </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-primary rounded-circle fw-bold"> K </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-secondary rounded-circle fw-bold"> H </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-warning rounded-circle fw-bold"> L </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-info rounded-circle fw-bold"> G </span>
                                </div>
                            </div>
                        </b-col>
                        <b-col xl="3">
                            <div class="avatar-group">
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-success-subtle text-success rounded-circle fw-bold shadow"> D </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-primary-subtle text-primary rounded-circle fw-bold shadow"> K </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-secondary-subtle text-secondary rounded-circle fw-bold shadow"> H </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-warning-subtle text-warning rounded-circle fw-bold shadow"> L </span>
                                </div>
                                <div class="avatar avatar-sm">
                                    <span class="avatar-title bg-info-subtle text-info rounded-circle fw-bold shadow"> G </span>
                                </div>
                            </div>
                        </b-col>
                        <b-col xl="3">
                            <div class="avatar-group">
                                <div
                                    class="avatar"
                                    data-bs-toggle="tooltip"
                                    data-bs-custom-class="tooltip-secondary"
                                    data-bs-placement="top"
                                    title="Vicki"
                                >
                                    <img :src="avatar10" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div class="avatar avatar-sm" data-bs-toggle="tooltip" data-bs-placement="top" title="Thomas">
                                    <span class="avatar-title bg-dark rounded-circle fw-bold"> T </span>
                                </div>
                                <div
                                    class="avatar"
                                    data-bs-toggle="tooltip"
                                    data-bs-custom-class="tooltip-warning"
                                    data-bs-placement="top"
                                    title="Kevin"
                                >
                                    <img :src="avatar7" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div
                                    class="avatar"
                                    data-bs-toggle="tooltip"
                                    data-bs-custom-class="tooltip-info"
                                    data-bs-placement="top"
                                    title="Chris"
                                >
                                    <img :src="avatar1" alt="" class="rounded-circle avatar-sm" />
                                </div>
                                <div
                                    class="avatar avatar-sm"
                                    data-bs-toggle="tooltip"
                                    data-bs-custom-class="tooltip-danger"
                                    data-bs-placement="top"
                                    title="15 more Users"
                                >
                                    <span class="avatar-title bg-danger rounded-circle fw-bold"> 9+ </span>
                                </div>
                            </div>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Images Shapes">
                    <b-row>
                        <b-col sm="2">
                            <img :src="small2" alt="img" class="img-fluid rounded" width="200" />
                            <p class="mb-0">
                                <code>.rounded</code>
                            </p>
                        </b-col>

                        <b-col sm="2" class="text-center">
                            <img :src="avatar2" alt="img" class="img-fluid rounded" width="120" />
                            <p class="mb-0">
                                <code>.rounded</code>
                            </p>
                        </b-col>

                        <b-col sm="2" class="text-center">
                            <img :src="avatar7" alt="img" class="img-fluid rounded-circle" width="120" />
                            <p class="mb-0">
                                <code>.rounded-circle</code>
                            </p>
                        </b-col>

                        <b-col sm="2">
                            <img :src="small1" alt="img" class="img-fluid img-thumbnail" width="200" />
                            <p class="mb-0">
                                <code>.img-thumbnail</code>
                            </p>
                        </b-col>
                        <b-col sm="2">
                            <img :src="avatar8" alt="img" class="img-fluid rounded-circle img-thumbnail" width="120" />
                            <p class="mb-0">
                                <code>.rounded-circle .img-thumbnail</code>
                            </p>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import avatar1 from '@/images/users/avatar-1.jpg';
import avatar10 from '@/images/users/avatar-10.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import small1 from '@/images/small/small-1.jpg';
import small2 from '@/images/small/small-2.jpg';
</script>
