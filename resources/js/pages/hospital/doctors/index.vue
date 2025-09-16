<template>
    <VerticalLayout>
        <PageTitle title="Doctors" subtitle="Hospital" />

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-light">
                        <h4 class="header-title">Doctor List</h4>
                        <div>
                            <Link href="/hospital/doctors/add" class="btn btn-success bg-gradient me-1"><i class="ti ti-plus"></i> Add Doctor </Link>
                            <a href="#" class="btn btn-secondary bg-gradient"><i class="ti ti-file-import me-1"></i> Import</a>
                        </div>
                    </b-card-header>
                    <b-table-simple responsive class="table-nowrap mb-0">
                        <b-thead class="bg-light-subtle">
                            <b-tr>
                                <b-th class="ps-3" style="width: 50px">
                                    <input type="checkbox" class="form-check-input" id="customCheck1" />
                                </b-th>
                                <b-th>Doctor ID</b-th>
                                <b-th>Doctor Name</b-th>
                                <b-th>Specialty</b-th>
                                <b-th>Phone Number</b-th>
                                <b-th>Email</b-th>
                                <b-th>Timing</b-th>
                                <b-th>Years of Experience</b-th>
                                <b-th class="text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>

                        <b-tbody>
                            <b-tr v-for="(doctor, idx) in doctors" :key="idx">
                                <b-td class="ps-3">
                                    <b-form-checkbox />
                                </b-td>
                                <b-td>{{ doctor.id }}</b-td>
                                <b-td>
                                    <img :src="doctor.image" class="avatar-sm rounded-circle me-2" alt="..." />
                                    <Link :href="doctor.url" class="text-dark fw-medium">
                                        {{ doctor.name }}
                                    </Link>
                                </b-td>
                                <b-td>{{ doctor.specialty }}</b-td>
                                <b-td>
                                    {{ doctor.contactNo }}
                                </b-td>
                                <b-td>
                                    {{ doctor.email }}
                                </b-td>
                                <b-td> {{ doctor.timing.days }}: {{ doctor.timing.time }} </b-td>
                                <b-td> {{ doctor.experience }} Years </b-td>
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
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { doctors } from '@/pages/hospital/doctors/components/data';
import { Link } from '@inertiajs/vue3';
import { ref } from 'vue';

const currentPage = ref(1);
</script>
