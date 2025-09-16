<template>
    <VerticalLayout>
        <PageTitle title="Doctor Details" subtitle="Hospital" />

        <b-row>
            <b-col xl="4" lg="12">
                <b-card no-body>
                    <b-card-body>
                        <div class="dr-profile-bg rounded-top position-relative mx-n3 mt-n3 position-relative">
                            <img
                                :src="avatar3"
                                alt=""
                                class="border-light rounded-circle position-absolute translate-middle start-50 top-100 border-3 border"
                                height="100"
                                width="100"
                            />
                        </div>
                        <div class="mb-2 mt-4 pt-3 text-center">
                            <p class="fs-18 fw-semibold text-dark mb-1">Dr. James Roger</p>
                            <p class="fw-medium text-muted mb-0">(MD , Cardiology)</p>
                        </div>
                        <b-card-title tag="h5" class="fw-semibold">About Doctor :</b-card-title>
                        <p class="mt-2">
                            Dr. James Roger has vast experience in treating cardiovascular diseases. He has presented papers at national and
                            international levels. Currently, he is a Professor and Head at a Medical College specializing in Cardiology.
                        </p>
                        <div class="mt-3">
                            <b-row>
                                <b-col lg="3" cols="3">
                                    <h5>Operations</h5>
                                    <p class="fs-15 text-dark fw-semibold mb-0">1302</p>
                                </b-col>

                                <b-col lg="3" cols="3">
                                    <h5>Hospital</h5>
                                    <p class="fs-15 text-dark fw-semibold mb-0">40</p>
                                </b-col>

                                <b-col lg="3" cols="3">
                                    <h5>Patient</h5>
                                    <p class="fs-15 text-dark fw-semibold mb-0">3412</p>
                                </b-col>

                                <b-col lg="3" cols="3">
                                    <h5>Experience</h5>
                                    <p class="fs-15 text-dark fw-semibold mb-0">14 Year</p>
                                </b-col>
                            </b-row>
                        </div>
                        <b-card-title tag="h5" class="fw-semibold mb-2 mt-4">Doctor Level :</b-card-title>
                        <b-badge variant="light" pill class="text-dark fw-medium fs-12 my-1 px-2 py-1">General Cardiology</b-badge>
                        <b-badge variant="light" pill class="text-dark fw-medium fs-12 my-1 px-2 py-1">Echocardiology</b-badge>
                        <b-badge variant="light" pill class="text-dark fw-medium fs-12 my-1 px-2 py-1">Senior Doctor</b-badge>
                        <b-badge variant="light" pill class="text-dark fw-medium fs-12 my-1 px-2 py-1">CHO</b-badge>
                        <b-badge variant="light" pill class="text-dark fw-medium fs-12 mt-1 px-2 py-1">Adult Congenital Cardiology </b-badge>
                        <b-card-title tag="h5" class="fw-semibold mt-4">Doctor Rating :</b-card-title>
                        <div class="d-inline-flex align-items-center fs-20 flex-grow-1 mt-1">
                            <span class="ti ti-star-filled text-warning"></span>
                            <span class="ti ti-star-filled text-warning"></span>
                            <span class="ti ti-star-filled text-warning"></span>
                            <span class="ti ti-star-filled text-warning"></span>
                            <span class="ti ti-star-half text-warning"></span>
                            <span class="fs-14 ms-1">4.6k Reviews </span>
                        </div>
                    </b-card-body>
                    <b-card-footer class="border-top hstack gap-1">
                        <a href="#" class="btn btn-primary w-100">Send Message</a>
                        <a href="#" class="btn btn-light w-100">Follow</a>
                        <a href="#" class="btn btn-dark d-inline-flex align-items-center justify-content-center avatar-md rounded"
                            ><i class="ti ti-edit fs-20"></i
                        ></a>
                    </b-card-footer>
                </b-card>

                <AppointmentForm />
            </b-col>

            <b-col xl="8" lg="12">
                <b-card no-body>
                    <b-card-body>
                        <b-row>
                            <b-col xl="6" lg="12">
                                <div>
                                    <b-card-title tag="h4">Personal Information :</b-card-title>
                                    <div class="mt-3 rounded border border-dashed px-2 py-1">
                                        <b-table-simple responsive borderless class="m-0">
                                            <b-tbody>
                                                <b-tr>
                                                    <b-td>
                                                        <p class="mb-0">Doctor Name :</p>
                                                    </b-td>
                                                    <b-td class="text-dark fw-medium fs-14 px-2">James D. Roger</b-td>
                                                </b-tr>
                                                <b-tr>
                                                    <b-td>
                                                        <p class="mb-0">Position :</p>
                                                    </b-td>
                                                    <b-td class="text-dark fw-medium fs-14 px-2">General Cardiology , CHO</b-td>
                                                </b-tr>
                                                <b-tr>
                                                    <b-td>
                                                        <p class="mb-0">Mobile Number :</p>
                                                    </b-td>
                                                    <b-td class="text-dark fw-medium fs-14 px-2">+96 303-975-3491</b-td>
                                                </b-tr>
                                                <b-tr>
                                                    <b-td>
                                                        <p class="mb-0">Email Address :</p>
                                                    </b-td>
                                                    <b-td class="text-dark fw-medium fs-14 px-2">jamesdroger@armyspy.com</b-td>
                                                </b-tr>
                                                <b-tr>
                                                    <b-td>
                                                        <p class="mb-0">Location :</p>
                                                    </b-td>
                                                    <b-td class="text-dark fw-medium fs-14 px-2">1971 Carter Street Stoups, IL 63101, USA</b-td>
                                                </b-tr>
                                                <b-tr>
                                                    <b-td>
                                                        <p class="d-flex align-items-center mb-0 gap-1">Web Site :</p>
                                                    </b-td>
                                                    <b-td class="text-dark fw-medium fs-14 px-2"><a href="#">www.coderthemes.com </a></b-td>
                                                </b-tr>
                                            </b-tbody>
                                        </b-table-simple>
                                    </div>
                                </div>
                            </b-col>
                            <b-col xl="6" lg="12">
                                <div>
                                    <b-card-title tag="h4" class="card-title">Abilities :</b-card-title>
                                    <div class="d-flex align-items-center justify-content-between mb-1 mt-4">
                                        <p class="fs-15 fw-medium text-dark mb-0">Operations</p>
                                        <div>
                                            <p class="fs-15 fw-medium text-dark mb-0">40%</p>
                                        </div>
                                    </div>
                                    <b-progress size="md" variant="primary" :value="40" :max="100" height="10px" />
                                    <div class="d-flex align-items-center justify-content-between mb-1 mt-4">
                                        <p class="fs-15 fw-medium text-dark mb-0">Colestrol</p>
                                        <div>
                                            <p class="fs-15 fw-medium text-dark mb-0">50%</p>
                                        </div>
                                    </div>
                                    <b-progress size="md" variant="success" :value="50" :max="100" height="10px" />
                                    <div class="d-flex align-items-center justify-content-between mb-1 mt-4">
                                        <p class="fs-15 fw-medium text-dark mb-0">Therapy</p>
                                        <div>
                                            <p class="fs-15 fw-medium text-dark mb-0">34%</p>
                                        </div>
                                    </div>
                                    <b-progress size="md" variant="warning" :value="34" :max="100" height="10px" />
                                    <div class="d-flex align-items-center justify-content-between mb-1 mt-4">
                                        <p class="fs-15 fw-medium text-dark mb-0">Mediation</p>
                                        <div>
                                            <p class="fs-15 fw-medium text-dark mb-0">60%</p>
                                        </div>
                                    </div>
                                    <b-progress size="md" variant="info" :value="60" :max="100" height="10px" />
                                </div>
                            </b-col>
                        </b-row>
                        <b-card-title tag="h4" class="mt-3 pt-2">Experience :</b-card-title>
                        <b-row class="g-3 mt-1">
                            <b-col xl="6" lg="12">
                                <div class="d-flex align-items-center flex-wrap gap-3 rounded border p-2">
                                    <img :src="profile1" alt="" class="avatar-xl" />
                                    <div>
                                        <p class="text-dark fw-semibold fs-16 mb-1">Brentwood's Health Company Senior CHO</p>
                                        <p class="text-dark fw-medium fs-14 mb-1">ETN Doctor - Head - Online Consultation - Fulltime</p>
                                        <p class="mb-1">Dec 2020 - Present , 4 yer 1 mos</p>
                                        <p class="mb-1">Sharon Lane Michigan City, IN 46360</p>
                                    </div>
                                </div>
                            </b-col>
                            <b-col xl="6" lg="12">
                                <div class="d-flex align-items-center flex-wrap gap-3 rounded border p-2">
                                    <img :src="profile2" alt="" class="avatar-xl" />
                                    <div>
                                        <p class="text-dark fw-semibold fs-16 mb-1">Hospital Dynamics Head Doctor</p>
                                        <p class="text-dark fw-medium fs-14 mb-1">ETN Doctor - General Cardiology - Fulltime</p>
                                        <p class="mb-1">Dec 2016 - Nov 2020 , 5 yer 4 mos</p>
                                        <p class="mb-1">Friendship Lane Santa Clara, CA 95050</p>
                                    </div>
                                </div>
                            </b-col>
                            <b-col xl="6" lg="12">
                                <div class="d-flex align-items-center flex-wrap gap-3 rounded border p-2">
                                    <img :src="profile3" alt="" class="avatar-xl" />
                                    <div>
                                        <p class="text-dark fw-semibold fs-16 mb-1">Florida Multi Specialist Hospital</p>
                                        <p class="text-dark fw-medium fs-14 mb-1">ETN Doctor - Echocardiology - Fulltime</p>
                                        <p class="mb-1">Dec 2014 - Nov 2016 , 3 yer 2 mos</p>
                                        <p class="mb-1">Simpson Street Bloomington, IL 61701</p>
                                    </div>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
                <b-row>
                    <b-col lg="6">
                        <b-card no-body>
                            <b-card-header class="border-bottom border-dashed">
                                <b-card-title tag="h4" class="mb-0">Skills</b-card-title>
                            </b-card-header>
                            <b-card-body>
                                <ApexChart :chart="skillsChart" />
                            </b-card-body>
                        </b-card>
                    </b-col>
                    <b-col lg="6">
                        <b-card no-body>
                            <b-card-header class="border-bottom border-dashed">
                                <b-card-title tag="h4" class="mb-0">Specialty</b-card-title>
                            </b-card-header>
                            <div class="card-body">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:user-check-rounded-bold-duotone" class="fs-28 text-primary" />
                                    </div>

                                    <div>
                                        <p class="fs-15 mb-0">General adult cardiologists</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-3 gap-2">
                                    <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:hand-heart-bold-duotone" class="fs-28 text-primary" />
                                    </div>

                                    <div>
                                        <p class="fs-15 mb-0">Cardiac imaging specialists</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-3 gap-2">
                                    <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:clipboard-heart-bold-duotone" class="fs-28 text-primary" />
                                    </div>

                                    <div>
                                        <p class="fs-15 mb-0">Electrophysiologists</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-3 gap-2">
                                    <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:heart-pulse-bold-duotone" class="fs-28 text-primary" />
                                    </div>

                                    <div>
                                        <p class="fs-15 mb-0">Adult congenital heart specialists</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-3 gap-2">
                                    <div class="avatar-lg bg-light d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:heart-bold-duotone" class="fs-28 text-primary" />
                                    </div>

                                    <div>
                                        <p class="fs-15 mb-0">Heart surgeons</p>
                                    </div>
                                </div>
                            </div>
                        </b-card>
                    </b-col>
                </b-row>
                <b-row>
                    <b-col lg="12">
                        <b-card no-body>
                            <b-card-header class="border-bottom border-dashed">
                                <b-card-title tag="h4" class="mb-0">Today Appointment List</b-card-title>
                            </b-card-header>
                            <b-card-body class="p-0">
                                <b-table-simple responsive class="table-nowrap mb-0">
                                    <b-thead class="bg-light bg-opacity-25">
                                        <b-tr>
                                            <b-th class="ps-3" style="width: 50px">
                                                <b-form-checkbox />
                                            </b-th>
                                            <b-th>Patient Name</b-th>
                                            <b-th>Appointment Date</b-th>
                                            <b-th>Appointment Time</b-th>
                                            <b-th>Phone Number</b-th>
                                            <b-th>Reason for Visit</b-th>
                                            <b-th class="text-center" style="width: 125px">Action</b-th>
                                        </b-tr>
                                    </b-thead>
                                    <b-tbody>
                                        <b-tr v-for="(item, idx) in appointmentList" :key="idx">
                                            <b-td class="ps-3">
                                                <b-form-checkbox />
                                            </b-td>
                                            <b-td>
                                                <h5 class="text-dark mb-0">
                                                    <a href="#" class="text-dark">{{ item.patientName }}</a>
                                                </h5>
                                            </b-td>
                                            <b-td>{{ item.date }}</b-td>
                                            <b-td>
                                                {{ item.time }}
                                            </b-td>
                                            <b-td>
                                                {{ item.contactNo }}
                                            </b-td>
                                            <b-td>
                                                {{ item.reasonForVisit }}
                                            </b-td>
                                            <b-td class="pe-3">
                                                <div class="d-flex gap-2">
                                                    <a href="javascript:void(0);" class="btn btn-soft-primary btn-icon btn-sm rounded-circle">
                                                        <i class="ti ti-eye"></i
                                                    ></a>
                                                    <a href="javascript:void(0);" class="btn btn-soft-success btn-icon btn-sm rounded-circle">
                                                        <i class="ti ti-edit"></i
                                                    ></a>
                                                    <a href="javascript:void(0);" class="btn btn-soft-danger btn-icon btn-sm rounded-circle">
                                                        <i class="ti ti-trash"></i
                                                    ></a>
                                                </div>
                                            </b-td>
                                        </b-tr>
                                    </b-tbody>
                                </b-table-simple>
                            </b-card-body>
                        </b-card>
                    </b-col>
                </b-row>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import avatar3 from '@/images/users/avatar-3.jpg';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import AppointmentForm from '@/pages/hospital/doctors/details/components/AppointmentForm.vue';

import ApexChart from '@/components/ApexChart.vue';
import profile1 from '@/images/dr-profile/h-1.svg';
import profile2 from '@/images/dr-profile/h-2.svg';
import profile3 from '@/images/dr-profile/h-3.svg';
import { appointmentList, skillsChart } from '@/pages/hospital/doctors/details/components/data';
import { Icon } from '@iconify/vue';
</script>
