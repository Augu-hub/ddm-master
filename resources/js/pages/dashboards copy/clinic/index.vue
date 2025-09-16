<template>
    <VerticalLayout>
        <Head title="Clinic" />




        <b-row>
            <b-col lg="4">
                <b-card no-body>
                    <div class="d-flex card-header justify-content-between align-items-center border-bottom border-dashed">
                        <h4 class="header-title">My Calendar</h4>
                        <b-dropdown :variant="null" no-caret toggle-class="card-drop p-0">
                            <template #button-content>
                                <i class="ti ti-dots-vertical"></i>
                            </template>
                            <b-dropdown-item>Refresh Report</b-dropdown-item>
                            <b-dropdown-item>Export Report</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <b-card-body>
                        <FlatPicker id="calendar" class="d-none" :options="{ inline: true, dateFormat: 'd M, Y', defaultDate: 'today' }" />

                        <div class="mt-2 text-center">
                            <a href="#" class="btn btn-sm btn-primary">Schedule a Meeting <i class="ti ti-arrow-right ms-1"></i></a>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="8">
                <b-card no-body class="card-h-100">
                    <b-card-header class="d-flex border-bottom align-items-center flex-wrap gap-3 border-dashed">
                        <h4 class="header-title me-auto">Patients Statistics <span class="text-muted fw-normal fs-14">(609.5k Patients)</span></h4>

                        <div class="d-flex flex-wrap gap-1">
                            <b-button variant="light" size="sm"> All</b-button>
                            <b-button variant="light" size="sm" class="active"> 1M</b-button>
                            <b-button variant="light" size="sm"> 6M</b-button>
                            <b-button variant="light" size="sm"> 1Y</b-button>
                        </div>
                    </b-card-header>

                    <b-card-body class="pt-1">
                        <div dir="ltr">
                            <ApexChart :chart="patientsStatisticsChart" />
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col xxl="8">
                <b-card no-body>
                    <b-card-header class="d-flex justify-content-between align-items-center border-bottom border-dashed">
                        <h4 class="header-title">
                            Top Doctors <i class="ti ti-info-octagon text-muted ms-1" v-b-tooltip.top="'Based on user reviews and popularity.'"> </i>
                        </h4>

                        <b-dropdown :variant="null" no-caret toggle-class="card-drop p-0">
                            <template #button-content>
                                <i class="ti ti-dots-vertical"></i>
                            </template>
                            <b-dropdown-item>Refresh Report</b-dropdown-item>
                            <b-dropdown-item>Export Report</b-dropdown-item>
                        </b-dropdown>
                    </b-card-header>

                    <b-card-body>
                        <b-row class="align-items-center g-3">
                            <b-col xxl="4" md="6" v-for="(doctor, idx) in topDoctors" :key="idx">
                                <DoctorCard :doctor="doctor" />
                            </b-col>
                        </b-row>

                        <div class="mt-3 text-center">
                            <a href="#" class="btn btn-sm btn-secondary">See All Doctors <i class="ti ti-arrow-right ms-1"></i></a>
                        </div>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col xxl="4">
                <b-card no-body>
                    <b-card-header class="d-flex justify-content-between border-bottom align-items-center border-dashed">
                        <h4 class="header-title">Gender</h4>
                        <a href="javascript:void(0);" class="btn btn-sm btn-soft-primary">Generate Report <i class="ti ti-file-export ms-1"></i></a>
                    </b-card-header>

                    <b-card-body>
                        <ApexChart :chart="genderChart" />

                        <b-row class="mt-3">
                            <b-col sm="4" class="text-start">
                                <p class="text-muted mb-1">Male Patient</p>
                                <h4 class="mb-2">
                                    <span class="ti ti-man text-primary"></span>
                                    <span>159.5k</span>
                                </h4>
                                <b-badge :variant="null" class="fs-12 badge-soft-danger"><i class="ti ti-arrow-badge-down"></i> 3.91% </b-badge>
                            </b-col>
                            <b-col sm="4" class="text-center">
                                <p class="text-muted mb-1">Female Patient</p>
                                <h4 class="mb-2">
                                    <span class="ti ti-woman text-success"></span>
                                    <span>148.56k</span>
                                </h4>
                                <b-badge :variant="null" class="fs-12 badge-soft-success"><i class="ti ti-arrow-badge-up"></i> 8.72% </b-badge>
                            </b-col>
                            <b-col sm="4" class="text-end">
                                <p class="text-muted mb-1">Child Patient</p>
                                <h4 class="mb-2">
                                    <span class="ti ti-baby-carriage text-info"></span>
                                    <span>45.2k</span>
                                </h4>
                                <b-badge :variant="null" class="fs-12 badge-soft-success"><i class="ti ti-arrow-badge-up"></i> 1.05% </b-badge>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <b-card no-body>
                    <b-card-header class="d-flex justify-content-between align-items-center">
                        <h4 class="header-title">All Appointments</h4>
                        <a href="#" class="btn btn-sm btn-secondary">Add New <i class="ti ti-plus ms-1"></i></a>
                    </b-card-header>
                    <b-card-body class="p-0">
                        <b-table-simple responsive hover class="table-nowrap table-custom table-centered m-0">
                            <b-thead class="bg-light thead-sm bg-opacity-50">
                                <b-tr class="text-uppercase fs-12">
                                    <b-th v-for="(header, idx) in allAppointmentsTable.header" :key="idx" class="text-muted">
                                        {{ header }}
                                    </b-th>
                                </b-tr>
                            </b-thead>
                            <b-tbody>
                                <b-tr v-for="(item, idx) in allAppointmentsTable.body" :key="idx">
                                    <b-td>#{{ item.queue }}</b-td>
                                    <b-td
                                        ><a href="#" class="link-reset fw-medium">{{ item.name }}</a></b-td
                                    >
                                    <b-td>{{ toSentenceCase(item.gender) }}</b-td>
                                    <b-td>{{ item.age }}</b-td>
                                    <b-td>{{ item.appointmentFor }}</b-td>
                                    <b-td
                                        >{{ item.date }} <small class="text-muted">{{ item.time }}</small></b-td
                                    >
                                    <b-td>
                                        <img :src="item.assignedDoctor.image" alt="" class="avatar-xs rounded-circle me-1" />
                                        <a href="#" class="link-reset fw-medium">{{ item.assignedDoctor.name }}</a>
                                    </b-td>
                                    <b-td>
                                        <b-badge
                                            :variant="null"
                                            class="p-1"
                                            :class="
                                                item.status == 'completed'
                                                    ? 'bg-success-subtle text-success'
                                                    : item.status === 'scheduled'
                                                      ? 'bg-warning-subtle text-warning'
                                                      : 'bg-danger-subtle text-danger'
                                            "
                                        >
                                            {{ toSentenceCase(item.status) }}
                                        </b-badge>
                                    </b-td>
                                    <b-td>
                                        <a href="#" class="text-muted fs-20"> <i class="ti ti-edit"></i></a>
                                    </b-td>
                                </b-tr>
                            </b-tbody>
                        </b-table-simple>

                        <b-card-footer>
                            <div class="align-items-center justify-content-between row text-sm-start text-center">
                                <div class="col-sm">
                                    <div class="text-muted">
                                        Showing <span class="fw-semibold">7</span> of <span class="fw-semibold">1,243</span> Results
                                    </div>
                                </div>
                                <div class="col-sm-auto mt-sm-0 mt-3">
                                    <ul class="pagination pagination-boxed pagination-sm justify-content-center mb-0">
                                        <li class="page-item disabled">
                                            <a href="#" class="page-link"><i class="ti ti-chevron-left"></i></a>
                                        </li>
                                        <li class="page-item active">
                                            <a href="#" class="page-link">1</a>
                                        </li>
                                        <li class="page-item">
                                            <a href="#" class="page-link">2</a>
                                        </li>
                                        <li class="page-item">
                                            <a href="#" class="page-link">3</a>
                                        </li>
                                        <li class="page-item">
                                            <a href="#" class="page-link"><i class="ti ti-chevron-right"></i></a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </b-card-footer>
                    </b-card-body>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import FlatPicker from '@/components/FlatPicker.vue';
import { toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { allAppointmentsTable, genderChart, patientsStatisticsChart, statistics, topDoctors } from '@/pages/dashboards/clinic/components/data';
import DoctorCard from '@/pages/dashboards/clinic/components/DoctorCard.vue';
import StatisticCard from '@/pages/dashboards/clinic/components/StatisticCard.vue';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import { BInputGroup } from 'bootstrap-vue-next';

const date = ref('01 May to 15 May');
</script>
