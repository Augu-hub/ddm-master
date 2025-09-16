<template>
    <VerticalLayout>
        <PageTitle title="Hospital Contacts" subtitle="Hospital" />

        <b-row>
            <b-col xl="3" lg="6" v-for="(item, idx) in contacts" :key="idx">
                <ContactCard :item="item" />
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import ContactCard from '@/pages/hospital/contacts/components/ContactCard.vue';
import { contacts } from '@/pages/hospital/contacts/components/data';
</script>
