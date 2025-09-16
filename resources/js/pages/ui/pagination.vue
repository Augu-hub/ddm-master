<template>
    <VerticalLayout>
        <PageTitle title="Pagination" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Default Pagination">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="mb-0"
                        first-number
                        last-number
                        prev-text="«"
                        next-text="»"
                    />
                </UICard>

                <UICard title="Disabled and active states">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="15"
                        :per-page="perPageItem"
                        prev-text="Previous"
                        next-text="Next"
                        first-number
                        last-number
                    />
                </UICard>

                <UICard title="Alignment">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="15"
                        :per-page="perPageItem"
                        prev-text="Previous"
                        next-text="Next"
                        class="justify-content-center"
                        first-number
                        last-number
                    />
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="15"
                        :per-page="perPageItem"
                        prev-text="Previous"
                        next-text="Next"
                        class="justify-content-end mb-0"
                        first-number
                        last-number
                    />
                </UICard>

                <UICard title="Custom Icon Pagination">
                    <b-pagination v-model="currentPage" :total-rows="25" :per-page="perPageItem" class="pagination-boxed" first-number last-number>
                        <template #prev-text> <i class="ti ti-chevron-left" /></template>
                        <template #next-text> <i class="ti ti-chevron-right align-middle" /></template>
                    </b-pagination>

                    <b-pagination v-model="currentPage" :total-rows="25" :per-page="perPageItem" class="pagination-boxed" first-number last-number>
                        <template #prev-text>
                            <ArrowLeftIcon />
                        </template>
                        <template #next-text>
                            <ArrowRightIcon />
                        </template>
                    </b-pagination>

                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed mb-0"
                        first-number
                        last-number
                    >
                        <template #prev-text>
                            <Icon icon="solar:arrow-left-line-duotone" class="fs-18" />
                        </template>
                        <template #next-text>
                            <Icon icon="solar:arrow-right-line-duotone" class="fs-18" />
                        </template>
                    </b-pagination>
                </UICard>

                <UICard title="Custom Color Pagination">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed pagination-info"
                        first-number
                        last-number
                    >
                        <template #prev-text> <i class="ti ti-chevron-left" /></template>
                        <template #next-text> <i class="ti ti-chevron-right align-middle" /></template>
                    </b-pagination>

                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed pagination-secondary"
                        first-number
                        last-number
                    >
                        <template #prev-text>
                            <ArrowLeftIcon />
                        </template>
                        <template #next-text>
                            <ArrowRightIcon />
                        </template>
                    </b-pagination>

                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed pagination-dark mb-0"
                        first-number
                        last-number
                    >
                        <template #prev-text>
                            <Icon icon="solar:arrow-left-line-duotone" class="fs-18" />
                        </template>
                        <template #next-text>
                            <Icon icon="solar:arrow-right-line-duotone" class="fs-18" />
                        </template>
                    </b-pagination>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Sizing">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="15"
                        :per-page="perPageItem"
                        size="lg"
                        first-number
                        last-number
                        hide-goto-end-buttons
                        prev-text="«"
                        next-text="»"
                    />
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="15"
                        :per-page="perPageItem"
                        size="sm"
                        class="mb-0"
                        first-number
                        last-number
                        prev-text="«"
                        next-text="»"
                    />
                </UICard>

                <UICard title="Boxed Pagination">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed"
                        first-number
                        last-number
                        prev-text="«"
                        next-text="»"
                    />
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        size="lg"
                        class="pagination-boxed"
                        first-number
                        last-number
                        prev-text="«"
                        next-text="»"
                    />
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        size="sm"
                        class="pagination-boxed mb-0"
                        first-number
                        last-number
                        prev-text="«"
                        next-text="»"
                    />
                </UICard>

                <UICard title="Rounded Pagination">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-rounded pagination-boxed mb-0"
                        first-number
                        last-number
                        prev-text="«"
                        next-text="»"
                    />
                </UICard>

                <UICard title="Soft Pagination">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-soft-danger pagination-boxed mb-0"
                        first-number
                        last-number
                    >
                        <template #prev-text> <i class="ti ti-chevron-left" /></template>
                        <template #next-text> <i class="ti ti-chevron-right" /></template>
                    </b-pagination>
                </UICard>

                <UICard title="Gradient Color Pagination">
                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed pagination-gradient pagination-info"
                        first-number
                        last-number
                    >
                        <template #prev-text> <i class="ti ti-chevron-left" /></template>
                        <template #next-text> <i class="ti ti-chevron-right align-middle" /></template>
                    </b-pagination>

                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed pagination-gradient pagination-secondary"
                        first-number
                        last-number
                    >
                        <template #prev-text>
                            <ArrowLeftIcon />
                        </template>
                        <template #next-text>
                            <ArrowRightIcon />
                        </template>
                    </b-pagination>

                    <b-pagination
                        v-model="currentPage"
                        :total-rows="25"
                        :per-page="perPageItem"
                        class="pagination-boxed pagination-gradient pagination-dark mb-0"
                        first-number
                        last-number
                    >
                        <template #prev-text>
                            <Icon icon="solar:arrow-left-line-duotone" class="fs-18" />
                        </template>
                        <template #next-text>
                            <Icon icon="solar:arrow-right-line-duotone" class="fs-18" />
                        </template>
                    </b-pagination>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

const perPageItem = ref(5);
const currentPage = ref(1);

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import { Icon } from '@iconify/vue';
import { ArrowLeftIcon, ArrowRightIcon } from 'lucide-vue-next';
</script>
