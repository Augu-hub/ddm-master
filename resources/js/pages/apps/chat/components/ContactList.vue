<template>
    <b-collapse class="chat" horizontal id="chat-user-list" visible>
        <div class="chat-user-list border-end">
            <b-card-body class="border-bottom px-3 py-2">
                <div class="d-flex align-items-center gap-2 py-1">
                    <div class="chat-users">
                        <div class="avatar-lg chat-avatar-online">
                            <img :src="avatar1" class="img-fluid rounded-circle" alt="Chris Keller" />
                        </div>
                    </div>
                    <div class="flex-grow-1">
                        <h5 class="mb-0">
                            <a href="#!" class="text-reset lh-base">Dhanoo K.</a>
                        </h5>
                        <p class="text-muted mb-0">Admin</p>
                    </div>

                    <b-dropdown toggle-class="card-drop p-0 m-0" :variant="null" no-caret class="lh-1">
                        <template v-slot:button-content>
                            <Icon icon="solar:settings-outline" class="align-middle" />
                        </template>

                        <b-dropdown-item>
                            <i class="ti ti-user-plus fs-17 me-1 align-middle"></i>
                            <span class="align-middle">New Contact</span>
                        </b-dropdown-item>
                        <b-dropdown-item>
                            <i class="ti ti-users-plus fs-17 me-1 align-middle"></i>
                            <span class="align-middle">New Group</span>
                        </b-dropdown-item>
                        <b-dropdown-item>
                            <i class="ti ti-star fs-17 me-1 align-middle"></i>
                            <span class="align-middle">Favorites</span>
                        </b-dropdown-item>
                        <b-dropdown-item>
                            <i class="ti ti-archive fs-17 me-1 align-middle"></i>
                            <span class="align-middle">Archive Contacts</span>
                        </b-dropdown-item>
                    </b-dropdown>

                    <b-button size="sm" type="button" class="btn-icon btn-soft-danger d-xl-none flex-grow-0" v-b-toggle="'contact-list'">
                        <i class="ti ti-x fs-20"></i>
                    </b-button>
                </div>
            </b-card-body>

            <!-- Contact list -->
            <div class="d-flex flex-column">
                <simplebar class="users-list position-relative list-scroll">
                    <div class="px-3 py-2">
                        <div class="app-search py-1">
                            <input
                                type="text"
                                class="form-control border-light bg-light rounded-2 bg-opacity-50"
                                placeholder="Search something here..."
                            />
                            <i class="app-search-icon ti ti-search text-muted fs-16"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-center bg-body-secondary position-sticky z-1 top-0 px-3 py-2">
                        <Icon icon="solar:pin-bold-duotone" class="fs-18 text-muted" />
                        <h5 class="fw-semibold fs-14 mb-0 ms-1">Pinned</h5>
                    </div>

                    <template v-for="(item, idx) in contacts.filter((i) => i.isPinned)" :key="idx">
                        <Link :href="item.url" class="text-body d-block">
                            <div class="chat-users">
                                <div class="avatar-md chat-avatar-offline">
                                    <img v-if="item.image" :src="item.image" class="img-fluid rounded-circle" alt="" />
                                    <div
                                        v-else-if="item.icon"
                                        class="rounded-circle bg-secondary d-flex align-items-center justify-content-center h-100 w-100 text-white"
                                    >
                                        <i class="fs-20" :class="item.icon"></i>
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-circle bg-info d-flex align-items-center justify-content-center h-100 w-100 text-white"
                                    >
                                        <span class="fw-semibold">{{ getFirstCharacter(item.name) }}</span>
                                    </div>
                                </div>

                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="fs-13 mb-0 mt-0">
                                        <span class="text-muted fs-12 float-end">{{ item.timestamp }}</span>
                                        {{ item.name }}
                                    </h5>
                                    <p class="text-muted lh-1 mb-0 mt-1">
                                        <span v-if="item.unreadMessages" class="w-25 float-end text-end">
                                            <b-badge :variant="null" class="bg-danger-subtle text-danger">{{ item.unreadMessages }}</b-badge>
                                        </span>

                                        <span v-if="item.seen" class="text-success w-25 float-end text-end">
                                            <i class="ti ti-checks"></i>
                                        </span>

                                        <span v-if="item.sent" class="text-muted w-25 float-end text-end"><i class="ti ti-check"></i></span>

                                        <span v-if="item.lastMessage" class="d-inline-block text-truncate w-75 overflow-hidden">{{
                                            item.lastMessage
                                        }}</span>

                                        <span v-if="item.isTyping" class="d-inline-block text-primary fs-12 fw-semibold w-75">typing...</span>
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </template>

                    <div class="d-flex align-items-center bg-body-secondary position-sticky z-1 top-0 px-3 py-2">
                        <Icon icon="solar:chat-line-bold-duotone" class="fs-18 text-muted" />
                        <h5 class="fw-semibold fs-14 mb-0 ms-1">All Messages</h5>
                    </div>

                    <template v-for="(item, idx) in contacts.filter((i) => !i.isPinned)" :key="idx">
                        <Link :href="item.url" class="text-body d-block">
                            <div class="chat-users">
                                <div class="avatar-md chat-avatar-offline">
                                    <img v-if="item.image" :src="item.image" class="img-fluid rounded-circle" alt="" />
                                    <div
                                        v-else-if="item.icon"
                                        class="rounded-circle bg-secondary d-flex align-items-center justify-content-center h-100 w-100 text-white"
                                    >
                                        <i class="fs-20" :class="item.icon"></i>
                                    </div>
                                    <div
                                        v-else
                                        class="rounded-circle bg-info d-flex align-items-center justify-content-center h-100 w-100 text-white"
                                    >
                                        <span class="fw-semibold">{{ getFirstCharacter(item.name) }}</span>
                                    </div>
                                </div>

                                <div class="flex-grow-1 overflow-hidden">
                                    <h5 class="fs-13 mb-0 mt-0">
                                        <span class="text-muted fs-12 float-end">{{ item.timestamp }}</span>
                                        {{ item.name }}
                                    </h5>
                                    <p class="text-muted lh-1 mb-0 mt-1">
                                        <span v-if="item.unreadMessages" class="w-25 float-end text-end">
                                            <b-badge :variant="null" class="bg-danger-subtle text-danger">{{ item.unreadMessages }}</b-badge>
                                        </span>

                                        <span v-if="item.seen" class="text-success w-25 float-end text-end">
                                            <i class="ti ti-checks"></i>
                                        </span>

                                        <span v-if="item.sent" class="text-muted w-25 float-end text-end"><i class="ti ti-checks"></i></span>

                                        <span v-if="item.lastMessage" class="d-inline-block text-truncate w-75 overflow-hidden">{{
                                            item.lastMessage
                                        }}</span>

                                        <span v-if="item.isTyping" class="d-inline-block text-primary fs-12 fw-semibold w-75">typing...</span>
                                    </p>
                                </div>
                            </div>
                        </Link>
                    </template>
                </simplebar>
            </div>
        </div>
    </b-collapse>
</template>

<script setup lang="ts">
import { getFirstCharacter } from '@/helpers/change-casing';
import avatar1 from '@/images/users/avatar-1.jpg';
import { contacts } from '@/pages/apps/chat/components/data';
import { Icon } from '@iconify/vue';
import { Link } from '@inertiajs/vue3';
import simplebar from 'simplebar-vue';
</script>
