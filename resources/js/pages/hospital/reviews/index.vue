<template>
    <VerticalLayout>
        <PageTitle title="Reviews" subtitle="Hospital" />

        <b-row>
            <b-col xl="4" lg="6" v-for="(item, idx) in doctorReviews" :key="idx">
                <ReviewCard :item="item" />
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { doctorReviews } from '@/pages/hospital/reviews/components/data';
import ReviewCard from '@/pages/hospital/reviews/components/ReviewCard.vue';
</script>
