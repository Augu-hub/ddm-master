<template>
    <VerticalLayout>
        <PageTitle title="Sellers" subtitle="eCommerce" />

        <b-row class="row-cols-1 row-cols-md-2 row-cols-lg-3">
            <b-col xl="4" lg="6" v-for="(item, idx) in sellers" :key="idx">
                <SellerCard :item="item" />
            </b-col>
        </b-row>

        <b-pagination v-model="currentPage" :total-rows="30" :per-page="10" class="justify-content-end mb-0" />
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { sellers } from '@/pages/e-commerce/sellers/components/data';
import SellerCard from '@/pages/e-commerce/sellers/components/SellerCard.vue';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
