<template>
    <VerticalLayout>
        <PageTitle title="Products Grid" subtitle="eCommerce" />

        <b-row>
            <b-col xxl="3">
                <Filters />
            </b-col>

            <b-col xxl="9">
                <b-row>
                    <b-col lg="3" v-for="(item, idx) in categories" :key="idx">
                        <CategoryCard :item="item" />
                    </b-col>
                </b-row>

                <b-row>
                    <b-col lg="4" md="6" v-for="(product, idx) in products" :key="idx">
                        <ProductCard :product="product" />
                    </b-col>
                </b-row>

                <b-row class="align-items-center mb-4">
                    <div class="col-sm">
                        <div class="text-muted">Showing <span class="fw-semibold">10</span> of <span class="fw-semibold">35</span> Results</div>
                    </div>

                    <div class="col-sm-auto mt-sm-0 mt-3">
                        <b-pagination
                            v-model="currentPage"
                            :total-rows="30"
                            :per-page="10"
                            class="justify-content-end mb-0"
                            prev-text="Previous"
                            next-text="Next"
                        />
                    </div>
                </b-row>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import CategoryCard from '@/pages/e-commerce/products-grid/components/CategoryCard.vue';
import { categories, products } from '@/pages/e-commerce/products-grid/components/data';
import Filters from '@/pages/e-commerce/products-grid/components/Filters.vue';
import ProductCard from '@/pages/e-commerce/products-grid/components/ProductCard.vue';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
