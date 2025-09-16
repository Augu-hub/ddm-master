<template>
    <VerticalLayout>
        <PageTitle title="Form Picker" subtitle="Forms" />
        <b-row>
            <b-col lg="12">
                <UICard title="Flatpickr - Datepicker">
                    <form action="#">
                        <b-row class="gy-3">
                            <b-col lg="6">
                                <b-form-group label="Basic" label-for="date" label-class="mb-0">
                                    <FlatPicker id="date" v-model="date" :options="{ dateFormat: 'd M, Y' }" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="DateTime" label-for="dateTime" label-class="mb-0">
                                    <FlatPicker id="dateTime" v-model="dateTime" :options="{ dateFormat: 'd.m.y', enableTime: true }" />
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Human-Friendly Dates" label-for="humanDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="humanDate"
                                        v-model="humanDate"
                                        :options="{ dateFormat: 'd M, Y', altFormat: 'F j, Y', altInput: true }"
                                    />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="MinDate and MaxDate" label-for="minMaxDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="minMaxDate"
                                        v-model="minMaxDate"
                                        :options="{ dateFormat: 'd M, Y', minDate: '25 12, 2021', maxDate: '29 12,2021' }"
                                    />
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Default Date" label-for="defaultDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="defaultDate"
                                        v-model="defaultDate"
                                        :options="{ dateFormat: 'd M, Y', defaultDate: '25 12,2021' }"
                                    />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Disabling Dates" label-for="disableDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker id="disableDate" v-model="disableDate" :options="{ dateFormat: 'd M, Y', disable: ['15 12,2021'] }" />
                                </b-form-group>
                            </b-col>
                        </b-row>

                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Selecting Multiple Dates" label-for="multipleDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker id="multipleDate" v-model="multipleDate" :options="{ dateFormat: 'd M, Y', mode: 'multiple' }" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Range" label-for="rangeDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker id="rangeDate" v-model="rangeDate" :options="{ dateFormat: 'd M, Y', mode: 'range' }" />
                                </b-form-group>
                            </b-col>
                        </b-row>

                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Inline" label-for="inlineDate" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="inlineDate"
                                        v-model="inlineDate"
                                        :options="{ dateFormat: 'd M, Y', inline: true, defaultDate: 'today' }"
                                    />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Week Numbers" label-for="weekNumbers" label-class="mb-0" class="mt-3">
                                    <FlatPicker id="weekNumbers" v-model="weekNumbers" :options="{ dateFormat: 'd M, Y', weekNumbers: true }" />
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </form>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="12">
                <UICard title="Flatpickr - Datepicker">
                    <form action="#">
                        <b-row class="gy-3">
                            <b-col lg="6">
                                <b-form-group label="Timepicker" label-for="timepicker" label-class="mb-0">
                                    <FlatPicker
                                        id="timepicker"
                                        v-model="timepicker"
                                        :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }"
                                    />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="24-hour Time Picker" label-for="fullTimepicker" label-class="mb-0">
                                    <FlatPicker
                                        id="fullTimepicker"
                                        v-model="fullTimepicker"
                                        :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i', time_24hr: true }"
                                    />
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Time Picker w/ Limits" label-for="timepickerLimit" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="timepickerLimit"
                                        v-model="timepickerLimit"
                                        :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i', minTime: '16:00', maxTime: '22:30' }"
                                    />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Preloading Time" label-for="preloadingTime" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="preloadingTime"
                                        v-model="preloadingTime"
                                        :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i', defaultDate: '13:45' }"
                                    />
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Inline" label-for="inlineTime" label-class="mb-0" class="mt-3">
                                    <FlatPicker
                                        id="inlineTime"
                                        v-model="inlineTime"
                                        :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i', inline: true }"
                                    />
                                </b-form-group>
                            </b-col>
                        </b-row>
                    </form>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import FlatPicker from '@/components/FlatPicker.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

const date = ref('');
const dateTime = ref('');
const humanDate = ref('');
const minMaxDate = ref('');
const defaultDate = ref('');
const disableDate = ref('');
const multipleDate = ref('');
const rangeDate = ref('');
const inlineDate = ref('');
const weekNumbers = ref('');
const timepicker = ref('');
const fullTimepicker = ref('');
const timepickerLimit = ref('');
const preloadingTime = ref('');
const inlineTime = ref('');
</script>
