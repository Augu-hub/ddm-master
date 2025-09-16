<template>
    <VerticalLayout>
        <PageTitle title="Patients" subtitle="Hospital" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-light">
                        <h4 class="header-title">Manage Patients</h4>
                        <div>
                            <Link href="/hospital/patients/add" class="btn btn-success bg-gradient me-1"
                                ><i class="ti ti-plus me-1"></i> Add Patient
                            </Link>
                            <a href="#" class="btn btn-secondary bg-gradient"><i class="ti ti-file-import me-1"></i> Import</a>
                        </div>
                    </b-card-header>

                    <b-table-simple responsive class="table-nowrap mb-0">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 60px">
                                    <b-form-checkbox />
                                </b-th>
                                <b-th>ID</b-th>
                                <b-th>Name</b-th>
                                <b-th>Date of Birth</b-th>
                                <b-th>Gender</b-th>
                                <b-th>Blood Group</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Address</b-th>
                                <b-th>Primary Care Physician</b-th>
                                <b-th class="text-center" style="width: 125px">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr v-for="(patient, idx) in patientsList" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>{{ patient.id }}</b-td>
                                <b-td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img v-if="patient.image" :src="patient.image" class="avatar-sm rounded-circle" alt="..." />
                                        <div v-else class="avatar avatar-sm">
                                            <span class="avatar-title bg-info rounded-circle fw-bold">
                                                {{ getFirstCharacter(patient.name) }}
                                            </span>
                                        </div>
                                        <Link :href="patient.url" class="text-reset fw-medium">{{ patient.name }}</Link>
                                    </div>
                                </b-td>
                                <b-td>{{ patient.dob }}</b-td>
                                <b-td>
                                    <b-badge
                                        :variant="patient.gender === 'male' ? 'secondary' : patient.gender === 'female' ? 'warning' : 'info'"
                                        class="fs-11 p-1"
                                        >{{ toSentenceCase(patient.gender) }}
                                    </b-badge>
                                </b-td>
                                <b-td>
                                    {{ patient.bloodGroup }}
                                </b-td>
                                <b-td>
                                    {{ patient.contactNo }}
                                </b-td>
                                <b-td>
                                    {{ patient.address }}
                                </b-td>
                                <b-td>
                                    {{ patient.primaryCarePhysician.name }}
                                </b-td>
                                <b-td class="pe-3">
                                    <div class="hstack justify-content-end gap-1">
                                        <a href="#" class="btn btn-soft-primary btn-icon btn-sm rounded-circle"> <i class="ti ti-eye"></i></a>
                                        <a href="#" class="btn btn-soft-success btn-icon btn-sm rounded-circle"> <i class="ti ti-edit fs-16"></i></a>
                                        <a href="#" class="btn btn-soft-danger btn-icon btn-sm rounded-circle"> <i class="ti ti-trash"></i></a>
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
import { getFirstCharacter, toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { patientsList } from '@/pages/hospital/patients/components/data';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
