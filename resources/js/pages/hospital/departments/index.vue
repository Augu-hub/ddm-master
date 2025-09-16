<template>
    <VerticalLayout>
        <PageTitle title="Departments" subtitle="Hospital" />

        <b-row>
            <b-col xxl="3" xl="4" md="6" v-for="(item, idx) in departments" :key="idx">
                <DepartmentCard :item="item" />
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import DepartmentCard from '@/pages/hospital/departments/components/DepartmentCard.vue';
import { departments } from '@/pages/hospital/departments/components/data';
</script>
