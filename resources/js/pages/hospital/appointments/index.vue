<template>
    <VerticalLayout>
        <PageTitle title="Appointments" subtitle="Hospital" />

        <b-row>
            <b-col xxl="3" xl="6" md="6" v-for="(item, idx) in statistics" :key="idx">
                <StatisticCard :item="item" />
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-light">
                        <b-card-title tag="h4" class="mb-0">Appointments List</b-card-title>

                        <div class="d-flex flex-wrap gap-1">
                            <a href="#" class="btn btn-success bg-gradient"><i class="ti ti-plus me-1"></i> New Appointment</a>
                            <a href="#" class="btn btn-secondary bg-gradient"><i class="ti ti-file-import me-1"></i> Import</a>
                        </div>
                    </b-card-header>

                    <b-card-body class="p-0">
                        <b-table-simple responsive hover class="mb-0 text-nowrap">
                            <b-thead class="bg-light-subtle">
                                <b-tr>
                                    <b-th class="ps-3" style="width: 50px">
                                        <b-form-checkbox />
                                    </b-th>
                                    <b-th>Patient Name</b-th>
                                    <b-th>Age</b-th>
                                    <b-th>Department</b-th>
                                    <b-th>Doctor Name</b-th>
                                    <b-th>Appo. Date</b-th>
                                    <b-th>Appo. Time</b-th>
                                    <b-th>Mobile No</b-th>
                                    <b-th>Action</b-th>
                                </b-tr>
                            </b-thead>
                            <b-tbody>
                                <b-tr v-for="(item, idx) in appointmentsList" :key="idx">
                                    <b-td class="ps-3">
                                        <b-form-checkbox />
                                    </b-td>
                                    <b-td>{{ item.patient.name }}</b-td>
                                    <b-td>{{ item.patient.age }}</b-td>
                                    <b-td>{{ item.department }}</b-td>
                                    <b-td
                                        ><img :src="item.doctor.image" alt="" class="avatar-sm rounded-circle me-2" />
                                        {{ item.doctor.name }}
                                    </b-td>
                                    <b-td>{{ item.date }}</b-td>
                                    <b-td>{{ item.time }}</b-td>
                                    <b-td>{{ item.patient.contactNo }}</b-td>
                                    <b-td>
                                        <div class="hstack gap-1">
                                            <a href="#" class="btn btn-soft-primary btn-icon btn-sm rounded-circle"> <i class="ti ti-eye"></i></a>
                                            <a href="#" class="btn btn-soft-success btn-icon btn-sm rounded-circle">
                                                <i class="ti ti-edit fs-16"></i
                                            ></a>
                                            <a href="#" class="btn btn-soft-danger btn-icon btn-sm rounded-circle"> <i class="ti ti-trash"></i></a>
                                        </div>
                                    </b-td>
                                </b-tr>
                            </b-tbody>
                        </b-table-simple>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import StatisticCard from '@/pages/hospital/appointments/components/StatisticCard.vue';
import { appointmentsList, statistics } from '@/pages/hospital/appointments/components/data';
</script>
