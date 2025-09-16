<template>
    <VerticalLayout>
        <PageTitle title="Sweet Alert 2" subtitle="Extended UI" />
        <b-row>
            <b-col lg="6">
                <UICard title="A Basic Message">
                    <b-button type="button" variant="primary" id="sweetalert-basic">Click me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Title">
                    <b-button type="button" variant="primary" id="sweetalert-title">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="HTML">
                    <b-button type="button" variant="primary" id="custom-html-alert">Toggle HTML SweetAlert</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="All States">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" id="sweetalert-info" variant="info">Toggle Info</b-button>
                        <b-button type="button" id="sweetalert-warning" variant="warning">Toggle Warning</b-button>
                        <b-button type="button" id="sweetalert-error" variant="danger">Toggle Error</b-button>
                        <b-button type="button" id="sweetalert-success" variant="success">Toggle Success</b-button>
                        <b-button type="button" id="sweetalert-question" variant="primary">Toggle Question</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Long Content">
                    <b-button type="button" id="sweetalert-longcontent" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="With Confirm Button">
                    <b-button type="button" id="sweetalert-confirm-button" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="With Cancel Button">
                    <b-button type="button" id="sweetalert-params" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="With Image Header (Logo)">
                    <b-button type="button" id="sweetalert-image" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Auto Close">
                    <b-button type="button" id="sweetalert-close" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Position">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button variant="primary" id="position-top-start">Top Start</b-button>
                        <b-button variant="primary" id="position-top-end">Top End</b-button>
                        <b-button variant="primary" id="position-bottom-start">Bottom Starts</b-button>
                        <b-button variant="primary" id="position-bottom-end">Bottom End</b-button>
                    </div>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="With Custom Padding, Background">
                    <b-button type="button" id="custom-padding-width-alert" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Ajax Request">
                    <b-button type="button" id="ajax-alert" variant="secondary">Click Me</b-button>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { onMounted } from 'vue';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import 'sweetalert2/dist/sweetalert2.css';
import Swal from 'sweetalert2/dist/sweetalert2.js';
type TVueSwalInstance = typeof Swal & typeof Swal.fire;

onMounted(() => {
    // Basic
    const basic = document.getElementById('sweetalert-basic');
    if (basic) {
        basic.addEventListener('click', (e) => {
            (Swal as TVueSwalInstance).fire({
                title: 'Any fool can use a computer',
                customClass: {
                    confirmButton: 'btn btn-primary mt-2',
                },
                buttonsStyling: false,
                // showCloseButton: true
            });
        });
    }

    // A title with a text under
    const sweetalertTitle = document.getElementById('sweetalert-title');
    if (sweetalertTitle) {
        sweetalertTitle.addEventListener('click', (e) => {
            (Swal as TVueSwalInstance).fire({
                title: 'The Internet?',
                text: 'That thing is still around?',
                icon: 'question',
                customClass: {
                    confirmButton: 'btn btn-primary mt-2',
                },
                buttonsStyling: false,
                showCloseButton: true,
            });
        });
    }

    // HTML
    const htmlEle = document.getElementById('custom-html-alert');
    if (htmlEle) {
        htmlEle.addEventListener('click', function () {
            Swal.fire({
                title: '<i>HTML</i> <u>example</u>',
                icon: 'info',
                iconWidth: '72px',
                html: 'You can use <b>bold text</b>, ' + '<a href="#">links</a> ' + 'and other HTML tags',
                showCloseButton: true,
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-success me-2',
                    cancelButton: 'btn btn-danger',
                },
                buttonsStyling: false,
                confirmButtonText: '<i class="ti ti-thumb-up-filled me-1"></i> Great!',
                cancelButtonText: '<i class="ti ti-thumb-down-filled"></i>',
            });
        });
    }

    // All States
    const infoButton = document.getElementById('sweetalert-info');
    if (infoButton) {
        infoButton.addEventListener('click', (e) => {
            Swal.fire({
                text: "Here's an example of an info SweetAlert!",
                icon: 'info',
                buttonsStyling: false,
                confirmButtonText: 'Ok, got it!',
                customClass: {
                    confirmButton: 'btn btn-info',
                },
            });
        });
    }

    const warningButton = document.getElementById('sweetalert-warning');
    if (warningButton) {
        warningButton.addEventListener('click', (e) => {
            Swal.fire({
                text: "Here's an example of a warning SweetAlert!",
                icon: 'warning',
                buttonsStyling: false,
                confirmButtonText: 'Ok, got it!',
                customClass: {
                    confirmButton: 'btn btn-warning',
                },
            });
        });
    }

    const errorButton = document.getElementById('sweetalert-error');
    if (errorButton) {
        errorButton.addEventListener('click', (e) => {
            Swal.fire({
                text: "Here's an example of an error SweetAlert!",
                icon: 'error',
                buttonsStyling: false,
                confirmButtonText: 'Ok, got it!',
                customClass: {
                    confirmButton: 'btn btn-danger',
                },
            });
        });
    }

    const successButton = document.getElementById('sweetalert-success');
    if (successButton) {
        successButton.addEventListener('click', (e) => {
            Swal.fire({
                text: "Here's an example of a success SweetAlert!",
                icon: 'success',
                buttonsStyling: false,
                confirmButtonText: 'Ok, got it!',
                customClass: {
                    confirmButton: 'btn btn-success',
                },
            });
        });
    }

    const questionButton = document.getElementById('sweetalert-question');
    if (questionButton) {
        questionButton.addEventListener('click', (e) => {
            Swal.fire({
                text: "Here's an example of a question SweetAlert!",
                icon: 'question',
                buttonsStyling: false,
                confirmButtonText: 'Ok, got it!',
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
            });
        });
    }

    // Long Content
    const longContent = document.getElementById('sweetalert-longcontent');
    if (longContent) {
        longContent.addEventListener('click', function () {
            Swal.fire({
                imageUrl: 'https://placeholder.pics/svg/300x1500',
                imageHeight: 1500,
                imageAlt: 'A tall image',
                customClass: {
                    confirmButton: 'btn btn-primary mt-2',
                },
                buttonsStyling: false,
                showCloseButton: true,
            });
        });
    }

    // With Confirm Button
    const confirmButton = document.getElementById('sweetalert-confirm-button');
    if (confirmButton) {
        confirmButton.addEventListener('click', function () {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                customClass: {
                    confirmButton: 'btn btn-primary me-2 mt-2',
                    cancelButton: 'btn btn-danger mt-2',
                },
                confirmButtonText: 'Yes, delete it!',
                buttonsStyling: false,
                showCloseButton: true,
            }).then(function (result: any) {
                if (result.value) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Your file has been deleted.',
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn btn-primary mt-2',
                        },
                        buttonsStyling: false,
                    });
                }
            });
        });
    }

    // With Cancel Button
    const cancelButton = document.getElementById('sweetalert-params');
    if (cancelButton) {
        cancelButton.addEventListener('click', function () {
            Swal.fire({
                title: 'Are you sure?',
                text: "You won't be able to revert this!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'No, cancel!',
                customClass: {
                    confirmButton: 'btn btn-primary me-2 mt-2',
                    cancelButton: 'btn btn-danger mt-2',
                },
                buttonsStyling: false,
                showCloseButton: true,
            }).then(function (result: any) {
                if (result.value) {
                    Swal.fire({
                        title: 'Deleted!',
                        text: 'Your file has been deleted.',
                        icon: 'success',
                        customClass: {
                            confirmButton: 'btn btn-primary mt-2',
                        },
                        buttonsStyling: false,
                    });
                } else if (
                    // Read more about handling dismissals
                    result.dismiss === Swal.DismissReason.cancel
                ) {
                    Swal.fire({
                        title: 'Cancelled',
                        text: 'Your imaginary file is safe :)',
                        icon: 'error',
                        customClass: {
                            confirmButton: 'btn btn-primary mt-2',
                        },
                        buttonsStyling: false,
                    });
                }
            });
        });
    }

    // With Image Header (Logo)
    const imageButton = document.getElementById('sweetalert-image');
    if (imageButton) {
        imageButton.addEventListener('click', function () {
            Swal.fire({
                title: 'Sweet!',
                text: 'Modal with a custom image.',
                imageUrl: '/src/assets/images/logo-sm.png',
                imageHeight: 40,
                customClass: {
                    confirmButton: 'btn btn-primary mt-2',
                },
                buttonsStyling: false,
                // animation: false,
                showCloseButton: true,
            });
        });
    }

    // Auto Close
    const closeButton = document.getElementById('sweetalert-close');
    if (closeButton) {
        closeButton.addEventListener('click', function () {
            let timerInterval: any;
            Swal.fire({
                title: 'Auto close alert!',
                html: 'I will close in <strong></strong> seconds.',
                timer: 2000,
                timerProgressBar: true,
                showCloseButton: true,
                didOpen: function () {
                    Swal.showLoading();
                    timerInterval = setInterval(function () {
                        const content = Swal.getHtmlContainer();
                        if (content) {
                            const b = content.querySelector('b');
                            if (b) {
                                b.textContent = Swal.getTimerLeft();
                            }
                        }
                    }, 100);
                },
                onClose: function () {
                    clearInterval(timerInterval);
                },
            }).then(function (result: any) {
                /* Read more about handling dismissals below */
                if (result.dismiss === Swal.DismissReason.timer) {
                    console.log('I was closed by the timer');
                }
            });
        });
    }

    // Position
    // Top Start Alert
    const positionTopStart = document.querySelector('#position-top-start');
    if (positionTopStart) {
        positionTopStart.addEventListener('click', function () {
            Swal.fire({
                position: 'top-start',
                icon: 'success',
                text: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
            });
        });
    }

    // Top End Alert
    const positionTopEnd = document.querySelector('#position-top-end');
    if (positionTopEnd) {
        positionTopEnd.addEventListener('click', function () {
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                text: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
            });
        });
    }

    // Bottom Start Alert
    const positionBottomStart = document.querySelector('#position-bottom-start');
    if (positionBottomStart) {
        positionBottomStart.addEventListener('click', function () {
            Swal.fire({
                position: 'bottom-start',
                icon: 'success',
                text: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
            });
        });
    }

    // Bottom End Alert
    const positionBottomEnd = document.querySelector('#position-bottom-end');
    if (positionBottomEnd) {
        positionBottomEnd.addEventListener('click', function () {
            Swal.fire({
                position: 'bottom-end',
                icon: 'success',
                text: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
            });
        });
    }

    // With Custom Padding, Background
    const customPadding = document.querySelector('#custom-padding-width-alert');
    if (customPadding) {
        customPadding.addEventListener('click', function () {
            Swal.fire({
                title: 'Custom width, padding, background.',
                width: 600,
                padding: 100,
                customClass: {
                    confirmButton: 'btn btn-primary',
                },
                buttonsStyling: false,
                background: 'var(--osen-secondary-bg) url(/src/assets/images/small/small-5.jpg) no-repeat center',
            });
        });
    }

    // Ajax Request
    const ajaxRequest = document.getElementById('ajax-alert');
    if (ajaxRequest) {
        ajaxRequest.addEventListener('click', function () {
            Swal.fire({
                title: 'Submit email to run ajax request',
                input: 'email',
                showCancelButton: true,
                confirmButtonText: 'Submit',
                showLoaderOnConfirm: true,
                customClass: {
                    confirmButton: 'btn btn-primary me-2',
                    cancelButton: 'btn btn-danger',
                },
                buttonsStyling: false,
                showCloseButton: true,
                preConfirm: function (email: any) {
                    return new Promise(function (resolve, reject) {
                        setTimeout(function () {
                            if (email === 'taken@example.com') {
                                reject('This email is already taken.');
                            } else {
                                resolve('');
                            }
                        }, 2000);
                    });
                },
                allowOutsideClick: false,
            }).then(function (email: any) {
                Swal.fire({
                    icon: 'success',
                    title: 'Ajax request finished!',
                    customClass: {
                        confirmButton: 'btn btn-primary',
                    },
                    buttonsStyling: false,
                    html: 'Submitted email: ' + email,
                });
            });
        });
    }
});
</script>
