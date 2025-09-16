<template>
    <VerticalLayout>
        <PageTitle title="Add Patients" subtitle="Hospital" />

        <b-row>
            <b-col lg="7">
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Basic information</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Patient First Name" label-for="patientFirst" class="mb-3">
                                    <b-form-input type="text" id="patientFirst" placeholder="Enter First Name" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Doctor Last Name" label-for="patientLast" class="mb-3">
                                    <b-form-input type="text" id="patientLast" placeholder="Enter Last Name" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Email Address" label-for="email" class="mb-3">
                                    <b-form-input type="email" id="email" placeholder="Enter Email" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Phone Number" label-for="phoneNumber" class="mb-3">
                                    <b-form-input type="number" id="phoneNumber" placeholder="Enter Number" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6" class="mb-3">
                                <FlatPicker
                                    id="dob"
                                    label="Birth Date"
                                    placeholder="dd-mm-yyyy"
                                    :options="{ dateFormat: 'd M, Y' }"
                                    class="flatpickr-input"
                                    group-class="mb-3"
                                />
                            </b-col>

                            <b-col lg="6" class="mb-3">
                                <ChoicesSelect id="gender" label="Gender" class="my-md-0 me-sm-3 my-1">
                                    <option>Male</option>
                                    <option>Female</option>
                                </ChoicesSelect>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Age" label-for="age" class="mb-3">
                                    <b-form-input type="number" id="age" placeholder="Enter Age" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="weight" label-for="weight" class="mb-3">
                                    <b-form-input type="number" id="weight" placeholder="Enter Weight" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Height" label-for="height" class="mb-3">
                                    <b-form-input type="number" id="height" placeholder="Enter Height" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <ChoicesSelect id="marital-status" label="Marital Status" class="my-md-0 me-sm-3 my-1">
                                    <option>Married</option>
                                    <option>Unmarried</option>
                                </ChoicesSelect>
                            </b-col>
                            <b-col lg="12">
                                <b-form-group label="Patient Address">
                                    <b-form-textarea :rows="3" placeholder="Full Address" />
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
                        <b-card-title tag="h4" class="mb-0">Medical Information</b-card-title>
                    </b-card-header>
                    <b-card-body>
                        <b-row class="g-3">
                            <b-col lg="6">
                                <b-form-group label="Patient Blood Group" label-for="bloodGroup">
                                    <b-form-input type="text" id="bloodGroup" placeholder="Enter Blood Group" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Patient Blood Pressure" label-for="bloodPressure">
                                    <b-form-input type="text" id="bloodPressure" placeholder="Enter Blood Pressure" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Patient Sugar Level" label-for="sugarLevel">
                                    <b-form-input type="text" id="sugarLevel" placeholder="Enter Sugar Level" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Patient Condition" label-for="condition">
                                    <b-form-input type="text" id="condition" placeholder="Enter Condition" />
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
                <div class="mb-3 text-end">
                    <a href="#!" class="btn btn-primary me-1">Add Patient</a>
                    <a href="#!" class="btn btn-danger">Cancel</a>
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

const uploadedFiles = ref<File[]>([]);
</script>
