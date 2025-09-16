<template>
    <b-card no-body class="chat-content rounded-0 mb-0 shadow-none">
        <b-card-header class="border-bottom px-3 py-2">
            <div class="d-flex align-items-center justify-content-between py-1">
                <div class="d-flex align-items-center gap-2">
                    <a
                        href="#"
                        class="btn btn-sm btn-icon btn-soft-primary d-none d-xl-flex me-2"
                        v-b-toggle="'chat-user-list'"
                        data-bs-target="#chat-user-list"
                    >
                        <i class="ti ti-chevrons-left fs-20"></i>
                    </a>

                    <b-button size="sm" class="btn-icon btn-ghost-light text-dark d-xl-none d-flex" v-b-toggle="'contact-list'">
                        <i class="ti ti-menu-2 fs-20"></i>
                    </b-button>

                    <img :src="avatar5" class="avatar-lg rounded-circle" alt="" />

                    <div>
                        <h5 class="lh-base my-0">
                            <a href="#" class="text-reset">James Zavel</a>
                        </h5>
                        <p class="text-muted mb-0"><small class="ti ti-circle-filled text-success"></small> Active</p>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <a href="#" class="btn btn-sm btn-icon btn-ghost-light d-none d-xl-flex" v-b-tooltip.top="'Voice Call'">
                        <i class="ti ti-phone-call fs-20"></i>
                    </a>
                    <a href="#" class="btn btn-sm btn-icon btn-ghost-light d-none d-xl-flex" v-b-tooltip.top="'Voice Call'">
                        <i class="ti ti-video fs-20"></i>
                    </a>

                    <a href="#" class="btn btn-sm btn-icon btn-ghost-light d-xl-flex">
                        <i class="ti ti-info-circle fs-20"></i>
                    </a>
                </div>
            </div>
        </b-card-header>

        <div>
            <div class="chat-scroll p-3" data-simplebar>
                <ul class="chat-list" data-apps-chat="messages-list">
                    <template v-for="(item, idx) in messages" :key="idx">
                        <li class="chat-group" :class="{ odd: item.recipient }">
                            <img v-if="item.sender" :src="item.sender.image" class="avatar-sm rounded-circle" alt="avatar-5" />
                            <img v-if="item.recipient" :src="item.recipient.image" class="avatar-sm rounded-circle" alt="avatar-1" />
                            <div class="chat-body">
                                <div>
                                    <h6 v-if="item.sender" class="d-inline-flex">{{ item.sender.name }}</h6>
                                    <h6 v-if="item.recipient" class="d-inline-flex">{{ item.recipient.name }}</h6>
                                    <h6 class="d-inline-flex text-muted">{{ item.timestamp }}</h6>
                                </div>

                                <div v-for="message in item.messages" class="chat-message">
                                    <p>{{ message }}</p>

                                    <b-dropdown class="chat-actions" :variant="null" size="sm" no-caret toggle-class="btn-link p-0">
                                        <template v-slot:button-content>
                                            <i class="ti ti-dots-vertical"></i>
                                        </template>

                                        <b-dropdown-item><i class="ti ti-copy fs-14 me-1 align-text-top"></i> Copy Message </b-dropdown-item>
                                        <b-dropdown-item><i class="ti ti-edit-circle fs-14 me-1 align-text-top"></i>Edit </b-dropdown-item>
                                        <b-dropdown-item><i class="ti ti-trash fs-14 me-1 align-text-top"></i>Delete </b-dropdown-item>
                                    </b-dropdown>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>

            <div class="border-top position-sticky w-100 bottom-0 mb-0 p-3">
                <b-form class="row align-items-center g-2" name="chat-form" id="chat-form">
                    <div class="col-auto">
                        <b-button :variant="null" class="btn-icon btn-soft-warning">
                            <Icon icon="solar:smile-circle-outline" class="fs-20" />
                        </b-button>
                    </div>

                    <b-col>
                        <b-form-input type="text" placeholder="Type Message..." />
                    </b-col>

                    <div class="col-sm-auto">
                        <div class="d-flex align-items-center gap-1">
                            <b-button variant="success" class="btn-icon"><i class="ti ti-send"></i></b-button>
                            <a href="#" class="btn btn-icon btn-soft-primary"><i class="ti ti-microphone"></i> </a>
                            <a href="#" class="btn btn-icon btn-soft-primary"><i class="ti ti-paperclip"></i></a>
                        </div>
                    </div>
                </b-form>
            </div>
        </div>
    </b-card>
</template>

<script setup lang="ts">
import avatar5 from '@/images/users/avatar-5.jpg';
import { messages } from '@/pages/apps/chat/components/data';
import { Icon } from '@iconify/vue';
</script>
