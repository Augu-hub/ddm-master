<template>
    <VerticalLayout>
        <PageTitle title="Chat" subtitle="Apps" />

        <div class="card">
            <div class="chat d-flex">
                <b-offcanvas size="xxl" placement="start" id="contact-list" no-header body-class="p-0">
                    <ContactList />
                </b-offcanvas>

                <div class="d-md-block d-none">
                    <ContactList />
                </div>

                <ChatArea />
            </div>
        </div>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import ChatArea from '@/pages/apps/chat/components/ChatArea.vue';
import ContactList from '@/pages/apps/chat/components/ContactList.vue';
</script>
