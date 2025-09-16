<template>
    <VerticalLayout>
        <PageTitle title="File Uploads" subtitle="Forms" />
        <UICard title="Dropzone File Upload">
            <FileUpload v-model="uploadedFiles" />
        </UICard>
    </VerticalLayout>
</template>
<script setup lang="ts">
import FileUpload from '@/components/FileUpload.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';
const uploadedFiles = ref<File[]>([]);
</script>
