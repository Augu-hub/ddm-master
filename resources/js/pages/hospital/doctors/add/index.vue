<template>
    <VerticalLayout>
        <PageTitle title="Add Doctors" subtitle="Hospital" />

        <b-row>
            <b-col lg="7">
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Basic information</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Doctor First Name" label-for="doctorFirst" class="mb-3">
                                    <b-form-input type="text" id="doctorFirst" placeholder="Enter First Name" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Doctor Last Name" label-for="doctorLast" class="mb-3">
                                    <b-form-input type="text" id="doctorLast" placeholder="Your Last Name" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Email Address" label-for="email" class="mb-3">
                                    <b-form-input type="email" id="email" placeholder="Your Email" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Phone Number" label-for="phoneNumber" class="mb-3">
                                    <b-form-input type="number" id="phoneNumber" placeholder="Your Phone Number" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <FlatPicker
                                    id="dob"
                                    label="Birth Date"
                                    placeholder="dd-mm-yyyy"
                                    class="flatpickr-input"
                                    group-class="mb-3"
                                    :options="{ dateFormat: 'd M, Y' }"
                                />
                            </b-col>
                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="gender" label="Gender" class="my-md-0 me-sm-3 my-1">
                                        <option>Male</option>
                                        <option>Female</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Education" label-for="education" class="mb-3">
                                    <b-form-input type="text" id="education" placeholder="Enter Education" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="department" label="Department" class="my-md-0 me-sm-3 my-1">
                                        <option>Cardiology</option>
                                        <option>Dermatology</option>
                                        <option>Pediatrics</option>
                                        <option>Gastroenterology</option>
                                        <option>Orthopedics</option>
                                        <option>Neurology</option>
                                        <option>Psychiatry</option>
                                        <option>Oncology</option>
                                        <option>Endocrinology</option>
                                        <option>Ophthalmology</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="12">
                                <b-form-group label="Doctor Address" label-for="address" class="mb-3">
                                    <b-form-textarea id="address" :rows="3" placeholder="Full Address" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="12">
                                <b-form-group label="About Doctor" label-for="about" class="mb-3">
                                    <b-form-textarea id="about" :rows="5" placeholder="Write short line about doctor" />
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="5">
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Upload Profile Photo</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-col cols="12">
                            <FileUpload v-model="uploadedFiles" />
                        </b-col>
                    </b-card-body>
                </b-card>
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Doctor Availability</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-row>
                            <b-col md="12" class="mb-3">
                                <b-form-group label="Available Days:" label-class="fw-semibold">
                                    <b-form-checkbox-group
                                        v-model="selectedOption"
                                        :options="availabilityOptions"
                                        name="availability"
                                    ></b-form-checkbox-group>
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row class="g-3">
                            <b-col lg="6">
                                <FlatPicker
                                    id="from-time"
                                    label="From Time:"
                                    placeholder="12:00 PM"
                                    :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }"
                                />
                            </b-col>
                            <b-col lg="6">
                                <FlatPicker
                                    id="to-time"
                                    label="To Time:"
                                    placeholder="12:00 PM"
                                    :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }"
                                />
                            </b-col>
                            <b-col lg="12">
                                <FlatPicker
                                    id="dateSelect"
                                    label="Available Date:"
                                    label-class="fw-semibold"
                                    placeholder="dd-mm-yyyy to dd-mm-yyyy"
                                    :options="{ mode: 'range', dateFormat: 'd M, Y' }"
                                />
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>

                <div class="mb-3 text-end">
                    <a href="#!" class="btn btn-primary">Add Doctor</a>
                    <a href="#!" class="btn btn-danger ms-1">Cancel</a>
                </div>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ChoicesSelect from '@/components/ChoicesSelect.vue';
import FileUpload from '@/components/FileUpload.vue';
import FlatPicker from '@/components/FlatPicker.vue';
import PageTitle from '@/components/PageTitle.vue';

import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

const selectedOption = ref([]);
const availabilityOptions = [
    { value: 'Sun', text: 'Sun' },
    { value: 'Mon', text: 'Mon' },
    { value: 'Tue', text: 'Tue' },
    { value: 'Wen', text: 'Wen' },
    { value: 'Thu', text: 'Thu' },
    { value: 'Fri', text: 'Fri' },
    { value: 'Sat', text: 'Sat' },
];

const uploadedFiles = ref<File[]>([]);
</script>
