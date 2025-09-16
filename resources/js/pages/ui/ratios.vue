<template>
    <VerticalLayout>
        <PageTitle title="Ratio" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Responsive Ratio video 21:9">
                    <div class="ratio ratio-21x9">
                        <iframe src="https://www.youtube.com/embed/zpOULjyy-n8?rel=0"></iframe>
                    </div>
                </UICard>

                <UICard title="Responsive Ratio video 1:1">
                    <div class="ratio ratio-1x1">
                        <iframe src="https://www.youtube.com/embed/zpOULjyy-n8?rel=0"></iframe>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Responsive Ratio video 16:9">
                    <div class="ratio ratio-16x9">
                        <iframe src="https://www.youtube.com/embed/zpOULjyy-n8?rel=0"></iframe>
                    </div>
                </UICard>

                <UICard title="Responsive Ratio video 4:3">
                    <div class="ratio ratio-4x3">
                        <iframe src="https://www.youtube.com/embed/zpOULjyy-n8?rel=0"></iframe>
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
