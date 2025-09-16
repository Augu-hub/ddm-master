<template>
    <VerticalLayout>
        <PageTitle title="Dragula" subtitle="Extended UI" />
        <b-row>
            <b-col cols="12">
                <UICard title="Simple Drag and Drop Example">
                    <VueDraggableNext class="row" :list="simpleList">
                        <b-col md="4" v-for="(item, idx) in simpleList" :key="idx">
                            <b-card no-body class="mb-0 mt-3 text-white" :class="`bg-${item.variant}`">
                                <b-card-body>
                                    <blockquote class="card-bodyquote mb-0">
                                        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Integer posuere erat a ante.</p>
                                        <footer>Someone famous in <cite title="Source Title">Source Title</cite></footer>
                                    </blockquote>
                                </b-card-body>
                            </b-card>
                        </b-col>
                    </VueDraggableNext>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Move stuff between containers">
                    <b-row>
                        <b-col md="6">
                            <div class="bg-light p-lg-4 bg-opacity-50 p-2">
                                <h5 class="mt-0">Part 1</h5>
                                <VueDraggableNext group="tasks" :list="list1" class="py-2">
                                    <b-card no-body class="mb-0 mt-2" v-for="(item, idx) in list1" :key="idx">
                                        <b-card-body>
                                            <div class="d-flex align-items-start">
                                                <img :src="item.image" alt="img" class="d-none d-sm-block avatar-sm rounded-circle me-3" />
                                                <div class="w-100 overflow-hidden">
                                                    <h5 class="mb-1 mt-0">{{ item.name }}</h5>
                                                    <p>{{ item.role }}</p>
                                                    <p class="text-muted mb-0">
                                                        <span class="fst-italic"
                                                            ><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably
                                                            haven't heard of them tattooed master cleanse live-edge keffiyeh.</span
                                                        >
                                                    </p>
                                                </div>
                                            </div>
                                        </b-card-body>
                                    </b-card>
                                </VueDraggableNext>
                            </div>
                        </b-col>
                        <b-col md="6">
                            <div class="bg-light p-lg-4 bg-opacity-50 p-2">
                                <h5 class="mt-0">Part 2</h5>
                                <VueDraggableNext group="tasks" :list="list2" class="py-2">
                                    <b-card no-body class="mb-0 mt-2" v-for="(item, idx) in list2" :key="idx">
                                        <b-card-body>
                                            <div class="d-flex align-items-start">
                                                <img :src="item.image" alt="img" class="d-none d-sm-block avatar-sm rounded-circle me-3" />
                                                <div class="w-100 overflow-hidden">
                                                    <h5 class="mb-1 mt-0">{{ item.name }}</h5>
                                                    <p>{{ item.role }}</p>
                                                    <p class="text-muted mb-0">
                                                        <span class="fst-italic"
                                                            ><b>"</b>Disrupt pork belly poutine, asymmetrical tousled succulents selfies. You probably
                                                            haven't heard of them tattooed master cleanse live-edge keffiyeh.</span
                                                        >
                                                    </p>
                                                </div>
                                            </div>
                                        </b-card-body>
                                    </b-card>
                                </VueDraggableNext>
                            </div>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Move stuff between containers using handle">
                    <b-row>
                        <b-col md="6">
                            <div class="bg-light p-lg-4 bg-opacity-50 p-2">
                                <h5 class="mt-0">Part 1</h5>
                                <VueDraggableNext group="tasks1" :list="list3" class="py-2">
                                    <b-card no-body class="mb-0 mt-2" v-for="(item, idx) in list3" :key="idx">
                                        <b-card-body>
                                            <div class="d-flex align-items-center">
                                                <img :src="item.image" alt="img" class="d-none d-sm-block avatar-sm rounded-circle me-3" />
                                                <div class="w-100 overflow-hidden">
                                                    <h5 class="mb-1">{{ item.name }}</h5>
                                                    <p class="mb-0">{{ item.role }}</p>
                                                </div>
                                                <span class="dragula-handle"></span>
                                            </div>
                                        </b-card-body>
                                    </b-card>
                                </VueDraggableNext>
                            </div>
                        </b-col>

                        <b-col md="6">
                            <div class="bg-light p-lg-4 bg-opacity-50 p-2">
                                <h5 class="mt-0">Part 2</h5>
                                <VueDraggableNext group="tasks1" :list="list4" class="py-2">
                                    <b-card no-body class="mb-0 mt-2" v-for="(item, idx) in list4" :key="idx">
                                        <b-card-body>
                                            <div class="d-flex align-items-center">
                                                <img :src="item.image" alt="img" class="d-none d-sm-block avatar-sm rounded-circle me-3" />
                                                <div class="w-100 overflow-hidden">
                                                    <h5 class="mb-1">{{ item.name }}</h5>
                                                    <p class="mb-0">{{ item.role }}</p>
                                                </div>
                                                <span class="dragula-handle"></span>
                                            </div>
                                        </b-card-body>
                                    </b-card>
                                </VueDraggableNext>
                            </div>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';
import { VueDraggableNext } from 'vue-draggable-next';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import avatar1 from '@/images/users/avatar-1.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar3 from '@/images/users/avatar-3.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
import avatar5 from '@/images/users/avatar-5.jpg';
import avatar6 from '@/images/users/avatar-6.jpg';
import avatar7 from '@/images/users/avatar-7.jpg';
import avatar8 from '@/images/users/avatar-8.jpg';
import avatar9 from '@/images/users/avatar-9.jpg';

const simpleList = ref([
    { id: '1', variant: 'primary' },
    { id: '2', variant: 'secondary' },
    { id: '3', variant: 'success' },
    { id: '4', variant: 'info' },
    { id: '5', variant: 'warning' },
    { id: '6', variant: 'danger' },
]);

const list1 = ref([
    { image: avatar1, name: 'Louis K. Bond', role: 'Founder & CEO' },
    { image: avatar2, name: 'Dennis N. Cloutier', role: 'Software Engineer' },
    { image: avatar3, name: 'Susan J. Sander', role: 'Web Designer' },
]);

const list2 = ref([
    { image: avatar4, name: 'James M. Short', role: 'Web Developer' },
    { image: avatar5, name: 'Gabriel J. Snyder', role: 'Business Analyst' },
    { image: avatar6, name: 'Louie C. Mason', role: 'Human Resources' },
]);

const list3 = ref([
    { image: avatar7, name: 'Louis K. Bond', role: 'Founder & CEO' },
    { image: avatar8, name: 'Dennis N. Cloutier', role: 'Software Engineer' },
    { image: avatar9, name: 'Susan J. Sander', role: 'Web Designer' },
]);

const list4 = ref([
    { image: avatar1, name: 'James M. Short', role: 'Web Developer' },
    { image: avatar5, name: 'Gabriel J. Snyder', role: 'Business Analyst' },
    { image: avatar3, name: 'Louie C. Mason', role: 'Human Resources' },
]);
</script>
