<template>
    <VerticalLayout>
        <PageTitle title="Patient Details" subtitle="Hospital" />

        <b-row>
            <b-col xl="4" lg="12">
                <b-card no-body>
                    <b-card-body>
                        <div class="d-flex align-items-center gap-2">
                            <img :src="avatar5" alt="" class="avatar-xl rounded-circle border-light border border-2" />
                            <div>
                                <h4 class="text-dark fw-medium">Ernest J. Johnson</h4>
                                <p class="text-muted mb-0">ID: PS49201</p>
                            </div>
                            <div class="ms-auto">
                                <b-badge variant="success" class="fs-12 px-2 py-1">Active</b-badge>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h4 class="fs-15">Contact Details :</h4>
                            <div class="mt-3">
                                <div class="d-flex align-items-center mb-2 gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded bg-opacity-75">
                                        <Icon icon="solar:point-on-map-bold-duotone" class="fs-20 text-primary" />
                                    </div>
                                    <p class="text-dark mb-0">2713 Frum Street Nashville, TN 37207</p>
                                </div>
                                <div class="d-flex align-items-center mb-2 gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded bg-opacity-75">
                                        <Icon icon="solar:smartphone-2-bold-duotone" class="fs-20 text-primary" />
                                    </div>
                                    <p class="text-dark mb-0">+ (901) 234.5678</p>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-flex align-items-center justify-content-center rounded bg-opacity-75">
                                        <Icon icon="solar:letter-bold-duotone" class="fs-20 text-primary" />
                                    </div>
                                    <p class="text-dark mb-0">2713 Frum Street Nashville, TN 37207</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3">
                            <h4 class="fs-15">Diagnosis Details :</h4>
                            <p class="text-muted fs-14 mb-0 mt-2">Hypertonic disease. Persistent atrial fibrillation. Chronic heart failure</p>
                        </div>
                        <div class="mt-3">
                            <h4 class="fs-15">Treatment :</h4>
                            <b-row class="g-2 mt-1">
                                <b-col lg="4" cols="6">
                                    <h4 class="fw-medium mb-0">10 G</h4>
                                    <p class="text-muted lh-lg mb-0">Dopamine</p>
                                </b-col>
                                <b-col lg="4" cols="6">
                                    <h4 class="fw-medium mb-0">5-8 G</h4>
                                    <p class="text-muted lh-lg mb-0">Salt Restriction/day</p>
                                </b-col>
                                <b-col lg="4" cols="6">
                                    <h4 class="fw-medium mb-0">1.5-2 L</h4>
                                    <p class="text-muted lh-lg mb-0">Fluid Restriction/day</p>
                                </b-col>
                                <b-col lg="4" cols="6">
                                    <h4 class="fw-medium mb-0">90MM</h4>
                                    <p class="text-muted lh-lg mb-0">Nitroglycerin for BP</p>
                                </b-col>
                            </b-row>
                        </div>
                    </b-card-body>
                    <b-card-footer class="border-top hstack gap-1 border-dashed">
                        <a href="#" class="btn btn-primary w-100">Open Chat</a>
                        <a href="#" class="btn btn-light w-100">Call Patient</a>
                        <a href="#" class="btn btn-dark d-inline-flex align-items-center justify-content-center avatar-md rounded"
                            ><i class="ti ti-edit fs-20"></i
                        ></a>
                    </b-card-footer>
                </b-card>

                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-block">
                                <b-card-title tag="h4" class="mb-0">Check Up File</b-card-title>
                            </div>
                            <div class="ms-auto">
                                <a href="#" class="btn btn-soft-primary btn-sm">View All</a>
                            </div>
                        </div>
                    </b-card-header>
                    <b-card-body>
                        <div
                            v-for="(file, idx) in checkUpFiles"
                            :key="idx"
                            class="d-flex align-items-center bg-light-subtle gap-2 rounded border border-dashed p-2"
                            :class="{ 'mt-2': idx != 0 }"
                        >
                            <div class="avatar avatar-lg d-flex align-items-center justify-content-center rounded-circle">
                                <Icon icon="solar:file-download-bold" class="text-primary fs-3" />
                            </div>
                            <div class="d-block">
                                <a href="#" class="text-dark fw-medium">{{ file.name }}</a>
                                <p class="text-muted fs-13 mb-0">{{ file.size }}</p>
                            </div>
                            <div class="ms-auto text-end">
                                <b-dropdown :variant="null" toggle-class="card-drop p-0" no-caret>
                                    <template v-slot:button-content>
                                        <i class="ti ti-dots-vertical"></i>
                                    </template>

                                    <b-dropdown-item>Download</b-dropdown-item>
                                    <b-dropdown-item>Share</b-dropdown-item>
                                </b-dropdown>
                            </div>
                        </div>
                    </b-card-body>
                </b-card>

                <b-card no-body>
                    <b-card-header class="d-flex align-items-center justify-content-between border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Water</b-card-title>
                        <div>
                            <p class="fs-15 text-dark fw-medium mb-0">Goal: 15/56</p>
                        </div>
                    </b-card-header>
                    <b-card-body>
                        <div class="custom-progress rounded">
                            <div class="progress-info d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="fw-medium fs-16 align-middle">Cups Tracked</span>
                                </div>
                                <span class="fw-medium text-muted float-end">56</span>
                            </div>
                            <div class="progress-data bg-primary rounded" style="width: 15%"></div>
                        </div>
                        <ApexChart :chart="waterChart" class="mt-5" />
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col xl="8" lg="12">
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Patient Information</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-row class="justify-content-between align-items-center">
                            <b-col lg="3" cols="6" class="border-end border-dashed">
                                <p class="text-muted mb-1">Gender</p>
                                <p class="fs-15 fw-medium mb-3">Male</p>
                                <p class="text-muted mb-1">Date Of Birth</p>
                                <p class="fs-15 fw-medium mb-0">1 January 1980</p>
                            </b-col>
                            <b-col lg="3" cols="6" class="border-end border-dashed">
                                <p class="text-muted mb-1">Phone</p>
                                <p class="fs-15 fw-medium mb-3">+ (901) 234.5678</p>
                                <p class="text-muted mb-1">City</p>
                                <p class="fs-15 fw-medium mb-0">New York</p>
                            </b-col>
                            <b-col lg="3" cols="6">
                                <p class="text-muted mb-1">Address</p>
                                <p class="fs-15 fw-medium mb-3">2713 Frum Street Nashville, TN</p>
                                <p class="text-muted mb-1">Register Date</p>
                                <p class="fs-15 fw-medium mb-0">18 May 2022</p>
                            </b-col>
                        </b-row>
                        <hr class="my-3" />
                        <h4 class="fs-15 fw-semibold mb-0">Body Status :</h4>
                        <b-row class="g-2 mt-2">
                            <b-col lg="2" cols="6">
                                <h3 class="fw-medium">22.3</h3>
                                <p class="text-muted mb-0">
                                    BIM <span class="text-success fw-medium"><i class="ti ti-arrow-down"></i>10</span>
                                </p>
                            </b-col>
                            <b-col lg="2" cols="6">
                                <h3 class="fw-medium">70 <span class="text-muted fs-15">kg</span></h3>
                                <p class="text-muted mb-0">
                                    Weight <span class="text-success fw-medium"><i class="ti ti-arrow-down"></i>13 kg</span>
                                </p>
                            </b-col>
                            <b-col lg="2" cols="6">
                                <h3 class="fw-medium">175 <span class="text-muted fs-15">cm</span></h3>
                                <p class="text-muted mb-0">Height</p>
                            </b-col>
                            <b-col lg="2" cols="6">
                                <h3 class="fw-medium">124/80</h3>
                                <p class="text-muted mb-0">
                                    Blood Pressure <span class="text-danger fw-medium"><i class="ti ti-arrow-up"></i>12</span>
                                </p>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>

                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <div class="d-flex align-items-center gap-2">
                            <div class="d-block">
                                <b-card-title tag="h4" class="mb-0">Medical History</b-card-title>
                            </div>
                            <div class="ms-auto">
                                <a href="#" class="btn btn-soft-primary btn-sm">Edit</a>
                            </div>
                        </div>
                    </b-card-header>
                    <b-card-body>
                        <b-row class="g-3">
                            <b-col lg="6">
                                <div class="d-flex align-items-center gap-2 rounded border p-2">
                                    <div class="avatar avatar-lg bg-danger-subtle d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:heart-pulse-bold" class="text-danger fs-32" />
                                    </div>
                                    <div>
                                        <p class="text-muted fw-medium fs-15 mb-1">Chronic Disease</p>
                                        <p class="text-dark fw-medium fs-15 mb-0">IHD, Obesity, Chronic thyroid disorder</p>
                                    </div>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <div class="d-flex align-items-center gap-2 rounded border p-2">
                                    <div class="avatar avatar-lg bg-primary-subtle d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:waterdrop-bold" class="text-primary fs-32" />
                                    </div>
                                    <div>
                                        <p class="text-muted fw-medium fs-15 mb-1">Diabetes Emergencies</p>
                                        <p class="text-dark fw-medium fs-15 mb-0">Diabetic Ketoacidosis</p>
                                    </div>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <div class="d-flex align-items-center gap-2 rounded border p-2">
                                    <div class="avatar avatar-lg bg-warning-subtle d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:medical-kit-bold" class="text-warning fs-32" />
                                    </div>
                                    <div>
                                        <p class="text-muted fw-medium fs-15 mb-1">Sugary</p>
                                        <p class="text-dark fw-medium fs-15 mb-0">Liposuction</p>
                                    </div>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <div class="d-flex align-items-center gap-2 rounded border p-2">
                                    <div class="avatar avatar-lg bg-info-subtle d-flex align-items-center justify-content-center rounded">
                                        <Icon icon="solar:users-group-two-rounded-bold" class="text-info fs-32" />
                                    </div>
                                    <div>
                                        <p class="text-muted fw-medium fs-15 mb-1">Family Disease</p>
                                        <p class="text-dark fw-medium fs-15 mb-0">Obesity (Father)</p>
                                    </div>
                                </div>
                            </b-col>
                            <div class="col-lg-12">
                                <div class="d-flex align-items-center gap-2 rounded border p-2">
                                    <div
                                        class="avatar avatar-lg bg-success-subtle d-flex align-items-center justify-content-center flex-shrink-0 rounded"
                                    >
                                        <Icon icon="solar:dropper-3-bold" class="text-success fs-32" />
                                    </div>
                                    <div>
                                        <p class="text-muted fw-medium fs-15 mb-1">Diabetes Related Complication</p>
                                        <p class="text-dark fw-medium fs-15 mb-0">Nephropathy, Neuropathy, Retinopathy, Diabetic foot</p>
                                    </div>
                                </div>
                            </div>
                        </b-row>
                    </b-card-body>
                </b-card>

                <b-row>
                    <b-col lg="6">
                        <b-card no-body>
                            <b-card-header class="border-bottom border-dashed">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-block">
                                        <b-card-title tag="h4" class="mb-0">Patient Statistic</b-card-title>
                                    </div>

                                    <div class="ms-auto">
                                        <b-dropdown :variant="null" size="sm" no-caret toggle-class="btn-soft-primary">
                                            <template v-slot:button-content> This Month </template>
                                            <b-dropdown-item>Download</b-dropdown-item>
                                            <b-dropdown-item>Export</b-dropdown-item>
                                            <b-dropdown-item>Import</b-dropdown-item>
                                        </b-dropdown>
                                    </div>
                                </div>
                            </b-card-header>
                            <b-card-body>
                                <ApexChart :chart="statisticChart" />
                                <div class="mt-4">
                                    <p class="text-primary fw-medium fs-16 mb-0">Total Visit 11</p>
                                </div>
                            </b-card-body>
                        </b-card>
                    </b-col>

                    <b-col lg="6">
                        <b-card no-body>
                            <b-card-header class="border-bottom border-dashed">
                                <div class="d-flex align-items-center gap-2">
                                    <div class="d-block">
                                        <b-card-title tag="h4" class="mb-0">Diet</b-card-title>
                                    </div>
                                    <div class="ms-auto">
                                        <a href="#" class="btn btn-soft-primary btn-sm">Add Note</a>
                                    </div>
                                </div>
                            </b-card-header>

                            <b-card-body>
                                <b-row class="g-2">
                                    <b-col lg="6">
                                        <div class="d-flex align-items-center gap-2 rounded border p-2">
                                            <div class="avatar d-flex align-items-center justify-content-center rounded-circle">
                                                <Icon icon="solar:wineglass-bold-duotone" class="text-primary fs-3" />
                                            </div>
                                            <div>
                                                <p class="text-muted fw-medium fs-14 mb-0">
                                                    <span class="fw-medium text-dark">8 Glass Water -</span>Per day
                                                </p>
                                            </div>
                                        </div>
                                    </b-col>
                                    <b-col lg="6">
                                        <div class="d-flex align-items-center gap-2 rounded border p-2">
                                            <div class="avatar d-flex align-items-center justify-content-center rounded-circle">
                                                <Icon icon="solar:cup-hot-bold-duotone" class="text-primary fs-3" />
                                            </div>
                                            <div>
                                                <p class="text-muted fw-medium fs-14 mb-0">
                                                    <span class="fw-medium text-dark">3 Black Tea -</span>Per day
                                                </p>
                                            </div>
                                        </div>
                                    </b-col>
                                </b-row>
                                <div class="d-flex align-items-center mt-2 gap-2 rounded border p-2">
                                    <div class="avatar d-flex align-items-center justify-content-center rounded-circle">
                                        <Icon icon="solar:chef-hat-heart-bold-duotone" class="text-primary fs-3" />
                                    </div>
                                    <div>
                                        <p class="text-dark fw-medium fs-14 mb-0">Intermittent fasting, Intermittent fasting,</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-2 gap-2 rounded border p-2">
                                    <div class="avatar d-flex align-items-center justify-content-center rounded-circle">
                                        <Icon icon="solar:chart-2-bold-duotone" class="text-primary fs-3" />
                                    </div>
                                    <div>
                                        <p class="text-dark fw-medium fs-14 mb-0">Table Sugar , Daily Avg 3/6</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-2 gap-2 rounded border p-2">
                                    <div class="avatar d-flex align-items-center justify-content-center rounded-circle">
                                        <Icon icon="solar:virus-bold-duotone" class="text-primary fs-3" />
                                    </div>
                                    <div>
                                        <p class="text-dark fw-medium fs-14 mb-0">Lactose , Beans</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center mt-2 gap-2 rounded border p-2">
                                    <div class="avatar d-flex align-items-center justify-content-center rounded-circle">
                                        <Icon icon="solar:sleeping-circle-bold-duotone" class="text-primary fs-3" />
                                    </div>
                                    <div>
                                        <p class="text-dark fw-medium fs-14 mb-0">8 H (Continues) Sleeping</p>
                                    </div>
                                </div>
                            </b-card-body>
                        </b-card>
                    </b-col>
                </b-row>

                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Treatment History</b-card-title>
                    </b-card-header>

                    <b-table-simple responsive size="sm" class="table-nowrap mb-0">
                        <b-thead class="bg-light bg-opacity-25">
                            <b-tr>
                                <b-th class="ps-3">ID</b-th>
                                <b-th>Type Treatment</b-th>
                                <b-th>Date</b-th>
                                <b-th>Result Treatment</b-th>
                                <b-th>Payment Status</b-th>
                                <b-th class="text-center" style="width: 120px">Action</b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr v-for="(treatment, idx) in treatmentHistory" :key="idx">
                                <b-td class="ps-3">{{ treatment.id }}</b-td>
                                <b-td>{{ treatment.type }}</b-td>
                                <b-td>
                                    {{ treatment.date }}
                                </b-td>
                                <b-td>
                                    <b-badge variant="light" class="text-dark fs-11 p-1">
                                        <i v-if="treatment.result === 'well-done'" class="ti ti-check text-success"></i>
                                        <i v-else class="ti ti-x text-danger"></i>
                                        {{ kebabToTitleCase(treatment.result) }}
                                    </b-badge>
                                </b-td>
                                <b-td>
                                    <b-badge
                                        :variant="
                                            treatment.paymentStatus === 'paid'
                                                ? 'success'
                                                : treatment.paymentStatus === 'pending'
                                                  ? 'warning'
                                                  : 'danger'
                                        "
                                        class="fs-11 p-1"
                                        >{{ toSentenceCase(treatment.paymentStatus) }}
                                    </b-badge>
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
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ApexChart from '@/components/ApexChart.vue';
import PageTitle from '@/components/PageTitle.vue';
import { kebabToTitleCase, toSentenceCase } from '@/helpers/change-casing';
import avatar5 from '@/images/users/avatar-5.jpg';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { checkUpFiles, statisticChart, treatmentHistory, waterChart } from '@/pages/hospital/patients/details/components/data';
import { Icon } from '@iconify/vue';
</script>
