<template>
    <VerticalLayout>
        <PageTitle title="Staffs" subtitle="Hospital" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-light">
                        <b-card-title tag="h4" class="mb-0">Hospital Staffs</b-card-title>

                        <div class="d-flex flex-wrap gap-1">
                            <a href="#" class="btn btn-success bg-gradient"><i class="ti ti-plus me-1"></i> Add Staffs</a>
                            <a href="#" class="btn btn-secondary bg-gradient"><i class="ti ti-file-import me-1"></i> Import</a>
                        </div>
                    </b-card-header>

                    <b-table-simple responsive hover class="mb-0 text-nowrap">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>Staff Name</b-th>
                                <b-th>Title/Position</b-th>
                                <b-th>Department</b-th>
                                <b-th>Contact Information</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Special Notes</b-th>
                                <b-th class="text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr v-for="(item, idx) in hospitalStaff" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img v-if="item.image" :src="item.image" class="avatar-sm rounded-circle" alt="..." />
                                        <div v-else class="avatar avatar-sm">
                                            <span class="avatar-title bg-warning rounded-circle fw-bold">
                                                {{ getFirstCharacter(item.name) }}
                                            </span>
                                        </div>
                                        <a href="#" class="text-dark fw-medium">{{ item.name }}</a>
                                    </div>
                                </b-td>
                                <b-td>{{ item.position }}</b-td>
                                <b-td>{{ item.department }}</b-td>
                                <b-td>{{ item.email }}</b-td>
                                <b-td>{{ item.contactNo }}</b-td>
                                <b-td>{{ item.note }}</b-td>
                                <b-td class="pe-3">
                                    <div class="hstack justify-content-end gap-1">
                                        <a href="javascript:void(0);" class="btn btn-soft-primary btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-eye"></i
                                        ></a>
                                        <a href="javascript:void(0);" class="btn btn-soft-success btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-edit fs-16"></i
                                        ></a>
                                        <a href="javascript:void(0);" class="btn btn-soft-danger btn-icon btn-sm rounded-circle">
                                            <i class="ti ti-trash"></i
                                        ></a>
                                    </div>
                                </b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>

                    <b-card-footer>
                        <b-pagination v-model="currentPage" :total-rows="30" :per-page="10" class="justify-content-end mb-0" />
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import { getFirstCharacter } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { hospitalStaff } from '@/pages/hospital/staffs/components/data';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
