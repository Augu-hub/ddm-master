<template>
    <div class="d-flex flex-column">
        <div class="d-flex align-items-center border-bottom flex-shrink-0 gap-2 border-dashed px-3 py-2">
            <div class="avatar-md">
                <img :src="avatar2" alt="" class="img-fluid rounded-circle" />
            </div>
            <div>
                <h5 class="fs-16 fw-semibold mb-1">
                    Chris K <i class="ti ti-rosette-discount-check-filled text-success" v-b-tooltip="'Pro User'"></i>
                </h5>
                <p class="fs-12 mb-0">Welcome!</p>
            </div>
            <button type="button" class="btn btn-sm btn-icon btn-soft-danger d-xl-none ms-auto" v-b-toggle="'file-manager-sidebar'">
                <i class="ti ti-x"></i>
            </button>
        </div>
        <div class="p-3">
            <div class="d-flex flex-column">
                <b-button variant="success" class="fw-medium drop-arrow-none dropdown-toggle w-100 mb-3">
                    Create New <i class="ti ti-plus ms-1"></i>
                </b-button>
                <div class="file-menu">
                    <a href="#" class="list-group-item active"><i class="ti ti-folder fs-18 me-2 align-middle"></i>My Files</a>
                    <a href="#" class="list-group-item"><i class="ti ti-brand-google-drive fs-18 me-2 align-middle"></i>Google Drive</a>
                    <a href="#" class="list-group-item"><i class="ti ti-share-3 fs-18 me-2 align-middle"></i>Share with me</a>
                    <a href="#" class="list-group-item"><i class="ti ti-clock fs-18 me-2 align-middle"></i>Recent</a>
                    <a href="#" class="list-group-item"><i class="ti ti-star fs-18 me-2 align-middle"></i>Starred</a>
                    <a href="#" class="list-group-item"><i class="ti ti-trash fs-18 me-2 align-middle"></i>Deleted Files</a>
                </div>

                <div class="mt-5 pt-5">
                    <b-alert v-model="showAlert" variant="secondary" class="mb-0 p-3 pt-0 text-center" role="alert">
                        <img :src="panda" alt="" class="img-fluid mt-n5" style="max-width: 135px" />
                        <div>
                            <h5 class="alert-heading fw-semibold fs-18 mt-2">Get more space for files</h5>
                            <p>We offer you unlimited storage space for all you needs</p>
                            <a href="#!" class="btn btn-secondary">Upgrade to Pro</a>
                        </div>
                    </b-alert>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup lang="ts">
import panda from '@/images/panda.svg';
import avatar2 from '@/images/users/avatar-1.jpg';
import { ref } from 'vue';

const showAlert = ref(true);
</script>
