<template>
    <b-card no-body>
        <b-card-body>
            <b-card-title tag="h4" class="mb-3">Appointment :</b-card-title>
            <div class="bg-light rounded border border-dashed bg-opacity-10 p-3">
                <b-form>
                    <FlatPicker id="schedule-date" label="Date" group-class="mb-2" placeholder="Select date" :options="{ dateFormat: 'd M, Y' }" />

                    <FlatPicker
                        id="schedule-time"
                        label="Time"
                        group-class="mb-2"
                        placeholder="12:00 PM"
                        :options="{ enableTime: true, noCalendar: true, dateFormat: 'H:i' }"
                    />

                    <b-form-group label="Name" label-for="schedule-name" class="mb-2">
                        <b-form-input type="text" id="schedule-name" placeholder="Your Full Name" />
                    </b-form-group>

                    <b-form-group label="Email" label-for="schedule-email" class="mb-2">
                        <b-form-input type="email" id="schedule-email" placeholder="Your Email" />
                    </b-form-group>

                    <b-form-group label="Phone number" label-for="schedule-number" class="mb-2">
                        <b-form-input type="number" id="schedule-number" placeholder="Your Phone number" />
                    </b-form-group>

                    <b-form-group label="Write Problems" label-for="schedule-textarea">
                        <b-form-textarea id="schedule-textarea" :rows="3" placeholder="Your Message" />
                    </b-form-group>
                </b-form>
            </div>
        </b-card-body>
        <b-card-footer class="border-top">
            <a href="#" class="btn btn-primary w-100">Book Appointment</a>
        </b-card-footer>
    </b-card>
</template>

<script setup lang="ts">
import FlatPicker from '@/components/FlatPicker.vue';
</script>
