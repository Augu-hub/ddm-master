<template>
    <VerticalLayout>
        <PageTitle title="Grid Js Tables" subtitle="Tables" />
        <b-row>
            <b-col lg="12">
                <UICard title="Base Example" title-class="mb-0 flex-grow-1">
                    <GridJsTable id="table-gridjs" :options="basicTableOptions" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Pagination" title-class="mb-0">
                    <GridJsTable id="table-pagination" :options="paginationTableOptions" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Search" title-class="mb-0">
                    <GridJsTable id="table-search" :options="searchTableOptions" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Sorting" title-class="mb-0">
                    <GridJsTable id="table-sorting" :options="sortingTableOptions" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Loading State" title-class="mb-0">
                    <GridJsTable id="table-loading-state" :options="loadingStateTableOptions" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Fixed Header" title-class="mb-0">
                    <GridJsTable id="table-fixed-header" :options="fixedHeaderTableOptions" />
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Hidden Columns" title-class="mb-0">
                    <GridJsTable id="table-hidden-column" :options="hiddenColumnsTableOptions" />
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import GridJsTable from '@/components/GridJsTable.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import {
    basicTableOptions,
    fixedHeaderTableOptions,
    hiddenColumnsTableOptions,
    loadingStateTableOptions,
    paginationTableOptions,
    searchTableOptions,
    sortingTableOptions,
} from '@/pages/tables/gridjs/components/data';
</script>
