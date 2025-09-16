import type { EventInput } from '@fullcalendar/core/index.js';

export type ExternalEventType = {
    id: number;
    textClass: string;
    className: string;
    title: string;
};

export type OptionsType = {
    value: string;
    text: string;
};

export const initialEvents: EventInput[] = [
    {
        id: '1',
        title: 'Interview - Backend Engineer',
        start: new Date(),
        className: 'bg-primary',
    },
    {
        id: '2',
        title: 'Meeting with CT Team',
        start: new Date(Date.now() + 13000000),
        className: 'bg-warning',
    },
    {
        id: '3',
        title: 'Meeting with Mr. Reback',
        start: new Date(Date.now() + 308000000),
        end: new Date(Date.now() + 338000000),
        className: 'bg-info',
    },
    {
        id: '4',
        title: 'Interview - Frontend Engineer',
        start: new Date(Date.now() + 60570000),
        end: new Date(Date.now() + 153000000),
        className: 'bg-secondary',
    },
    {
        id: '5',
        title: 'Phone Screen - Frontend Engineer',
        start: new Date(Date.now() + 168000000),
        className: 'bg-success',
    },
    {
        id: '6',
        title: 'Buy Design Assets',
        start: new Date(Date.now() + 330000000),
        end: new Date(Date.now() + 330800000),
        className: 'bg-primary',
    },
    {
        id: '7',
        title: 'Setup Github Repository',
        start: new Date(Date.now() + 1008000000),
        end: new Date(Date.now() + 1108000000),
        className: 'bg-danger',
    },
    {
        id: '8',
        title: 'Meeting with Mr. Shreyu',
        start: new Date(Date.now() + 2508000000),
        end: new Date(Date.now() + 2508000000),
        className: 'bg-dark',
    },
    {
        id: '8',
        title: 'Product Launch Strategy Meeting',
        start: new Date().setDate(new Date().getDate() - 9),
        className: 'bg-dark',
    },
];

// Options
export const options: OptionsType[] = [
    { value: 'bg-primary', text: 'Blue' },
    { value: 'bg-secondary', text: 'Gray Dark' },
    { value: 'bg-success', text: 'Green' },
    { value: 'bg-info', text: 'Cyan' },
    { value: 'bg-warning', text: 'Yellow' },
    { value: 'bg-danger', text: 'Red' },
    { value: 'bg-dark', text: 'Dark' },
];
