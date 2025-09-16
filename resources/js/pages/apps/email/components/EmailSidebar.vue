<template>
    <b-card-body>
        <div class="d-flex justify-content-between align-items-center mb-2 gap-2">
            <b-button variant="danger" type="button" class="fw-semibold w-100" @click="toggleComposeModal">Compose</b-button>

            <b-button size="sm" :variant="null" class="btn-icon btn-soft-danger d-xl-none ms-auto" v-b-toggle="'email-sidebar'">
                <i class="ti ti-x"></i>
            </b-button>
        </div>

        <div class="email-menu-list d-flex flex-column gap-1">
            <a href="#" class="active">
                <Icon icon="solar:inbox-bold-duotone" class="fs-18 text-muted me-2" />
                <span>Inbox</span>
                <span class="badge bg-danger-subtle fs-12 text-danger ms-auto">21</span>
            </a>

            <a href="#">
                <Icon icon="solar:map-arrow-right-bold-duotone" class="fs-18 text-muted me-2" />
                <span>Sent</span>
            </a>

            <a href="#">
                <Icon icon="solar:star-bold-duotone" class="fs-18 text-muted me-2" />
                <span>Starred</span>
            </a>

            <a href="#">
                <Icon icon="solar:clock-circle-bold-duotone" class="fs-18 text-muted me-2" />
                <span>Scheduled</span>
            </a>

            <a href="#">
                <Icon icon="solar:clapperboard-edit-bold-duotone" class="fs-18 text-muted me-2" />
                <span>Draft</span>
            </a>
        </div>
    </b-card-body>

    <b-card-body class="border-top border-light">
        <a role="button" class="btn-link d-flex align-items-center text-muted fw-bold fs-12 text-uppercase my-md-0 my-2" v-b-toggle="'other'"
            >Other <i class="ti ti-chevron-down ms-auto"></i>
        </a>

        <b-collapse visible id="other">
            <div class="email-menu-list d-flex flex-column mt-2 gap-1">
                <a href="#">
                    <Icon icon="solar:mailbox-bold-duotone" class="fs-18 text-muted me-2" />
                    <span>All Mail</span>
                </a>

                <a href="#">
                    <Icon icon="solar:trash-bin-trash-bold-duotone" class="fs-18 text-muted me-2" />
                    <span>Trash</span>
                </a>
                <a href="#">
                    <Icon icon="solar:info-square-bold-duotone" class="fs-18 text-muted me-2" />
                    <span>Spam</span>
                </a>
                <a href="#">
                    <Icon icon="solar:chat-round-line-bold-duotone" class="fs-18 text-muted me-2" />
                    <span>Chats</span>
                </a>
            </div>
        </b-collapse>
    </b-card-body>

    <div class="card-body border-top border-light">
        <a role="button" class="btn-link d-flex align-items-center text-muted fw-bold fs-12 text-uppercase my-md-0 my-2" v-b-toggle="'labels'"
            >Labels <i class="ti ti-chevron-down ms-auto"></i
        ></a>
        <b-collapse visible id="labels">
            <div class="email-menu-list d-flex flex-column mt-2 gap-1">
                <a href="#">
                    <Icon icon="solar:bolt-circle-bold-duotone" class="text-success fs-16 me-2" />
                    <span>Personal</span>
                </a>

                <a href="#">
                    <Icon icon="solar:bolt-circle-bold-duotone" class="text-danger fs-16 me-2" />
                    <span>Client</span>
                </a>

                <a href="#">
                    <Icon icon="solar:bolt-circle-bold-duotone" class="text-info fs-16 me-2" />
                    <span>Marketing</span>
                </a>

                <a href="#">
                    <Icon icon="solar:bolt-circle-bold-duotone" class="text-secondary fs-16 me-2" />
                    <span>Office</span>
                </a>
            </div>
        </b-collapse>
    </div>

    <!-- Mail Compose Modal -->
    <b-modal v-model="isComposeModalOpen" centered size="lg" header-class="py-2" footer-class="py-2">
        <template v-slot:header>
            <h4 class="modal-title" id="email-compose-modalLabel">New Message</h4>
            <button type="button" class="btn-close btn-close-white" @click="toggleComposeModal"></button>
        </template>

        <b-form>
            <b-form-group label="To" class="mb-2">
                <b-form-input type="text" placeholder="example@email.com" />
            </b-form-group>

            <b-form-group label="Subject" class="mb-2">
                <b-form-input type="text" placeholder="Your subject" />
            </b-form-group>

            <div>
                <label class="form-label">Message</label>
                <QuillEditor theme="snow" :toolbar="toolbar" style="height: 150px" :content="taskDetail" content-type="html" />
            </div>
        </b-form>

        <template v-slot:footer>
            <b-button variant="primary" @click="toggleComposeModal">Send Message</b-button>
            <b-button variant="light" @click="toggleComposeModal">Cancel</b-button>
        </template>
    </b-modal>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
import { QuillEditor } from '@vueup/vue-quill';
import { ref } from 'vue';

const isComposeModalOpen = ref(false);
const toggleComposeModal = () => {
    isComposeModalOpen.value = !isComposeModalOpen.value;
};

const toolbar = [[{ header: [1, 2, false] }], ['bold', 'italic', 'underline'], ['image', 'code-block']];

const taskDetail = `<p>Writing something...</p>`;
</script>
