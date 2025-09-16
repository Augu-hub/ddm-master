

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { computed, onMounted, reactive, ref } from 'vue';

import { initialEvents, options } from '@/pages/apps/calendar/components/data';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import { type CalendarOptions } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import interactionPlugin, { Draggable } from '@fullcalendar/interaction';
import listPlugin from '@fullcalendar/list';
import timeGridPlugin from '@fullcalendar/timegrid';
import FullCalendar from '@fullcalendar/vue3';
import { useVuelidate } from '@vuelidate/core';
import { required } from '@vuelidate/validators';

const modal = ref(false);
const eventData = ref();
const isEditEvent = ref(false);
const isDateClick = ref('');

const dateEvent = (e: any) => {
    isDateClick.value = e.date;
    toggleModal();
};

const toggleModal = () => {
    modal.value = !modal.value;
    v.value.$reset();
    isEditEvent.value = false;
    vuelidateState.eventName = undefined;
    vuelidateState.eventCategory = undefined;
};

const deleteEvent = () => {
    eventData.value.remove();
    toggleModal();
};

const editEvent = (info: any) => {
    toggleModal();
    isEditEvent.value = true;
    eventData.value = info.event;
    vuelidateState.eventName = eventData.value.title;
    vuelidateState.eventCategory = eventData.value.classNames[0];
};

const calendarOptions = ref<CalendarOptions>({
    plugins: [dayGridPlugin, timeGridPlugin, listPlugin, bootstrapPlugin, interactionPlugin],
    initialView: 'dayGridMonth',
    slotDuration: '00:30:00',
    slotMinTime: '07:00:00',
    slotMaxTime: '19:00:00',
    // themeSystem: 'bootstrap',
    bootstrapFontAwesome: false,
    buttonText: {
        today: 'Today',
        month: 'Month',
        week: 'Week',
        day: 'Day',
        list: 'List',
        prev: 'Prev',
        next: 'Next',
    },
    handleWindowResize: true,
    // height: window.innerHeight - 200,
    headerToolbar: {
        left: 'prev,next today',
        center: 'title',
        right: 'dayGridMonth,timeGridWeek,timeGridDay,listMonth',
    },
    droppable: true,
    editable: true,
    selectable: true,
    events: initialEvents,
    eventClick: editEvent,
    dateClick: dateEvent,
});

onMounted(() => {
    const ele = document.getElementById('external-events');

    if (ele) {
        new Draggable(ele, {
            itemSelector: '.external-event',
            eventData: function (eventEl: any) {
                return {
                    title: eventEl.innerText,
                    start: new Date(),
                    className: eventEl.getAttribute('data-class'),
                };
            },
        });
    }
});

const vuelidateState = reactive({
    eventName: undefined,
    eventCategory: undefined,
});

const vuelidateRules = computed(() => ({
    eventName: { required },
    eventCategory: { required },
}));

const v = useVuelidate(vuelidateRules, vuelidateState);

const fullCalendar = ref();

const handleVuelidateSubmit = async () => {
    const result = await v.value.$validate();
    if (result) {
        if (!isEditEvent.value) {
            const calendarApi = fullCalendar.value?.getApi();
            calendarApi.addEvent({
                id: (Math.floor(Math.random() * 100 + 20) - 20).toString(),
                title: vuelidateState.eventName,
                className: vuelidateState.eventCategory,
                start: isDateClick.value || new Date(),
            });
        } else {
            eventData.value.setProp('title', vuelidateState.eventName);
            eventData.value.setProp('classNames', [vuelidateState.eventCategory]);
            isEditEvent.value = false;
        }
        isDateClick.value = '';
        toggleModal();
    }
};
</script>
