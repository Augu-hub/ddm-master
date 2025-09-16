<template>
    <VerticalLayout>
        <PageTitle title="Timeline" subtitle="Pages" />

        <b-row class="justify-content-center">
            <b-col xxl="12">
                <div class="mb-4 text-center">
                    <h2 class="textdark fw-bold">Our Company Milestone</h2>
                </div>
                <div class="timeline" dir="ltr">
                    <div class="timeline-show mb-3 text-center">
                        <h5 class="time-show-name m-0">Today</h5>
                    </div>

                    <div class="timeline-lg-item timeline-item-left">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow-alt shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:check-circle-bold-duotone" class="text-success fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">01</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">23 May 2024</p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between">
                                        <p class="text-dark fw-semibold fs-16 mb-2">Project Completed: Achievements and Outcomes</p>
                                        <p class="mb-0">1hr Ago</p>
                                    </div>
                                    <p class="mb-1">
                                        <i class="ti ti-checks text-success fs-16 me-1"></i>Successfully met all project goals and objectives within
                                        the stipulated timeline.
                                    </p>
                                    <p class="mb-0">
                                        <i class="ti ti-checks text-success fs-16 me-1"></i>Delivered high-quality outputs that meet or exceed the
                                        expectations of stakeholders.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-lg-item timeline-item-right">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:user-bold-duotone" class="text-primary fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">02</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">23 May 2024</p>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center my-3 gap-2">
                                    <img src="@/images/users/avatar-10.jpg" alt="" class="avatar-lg rounded-circle border-light border border-2" />
                                    <div>
                                        <p class="text-dark fw-medium fs-15 mb-0">Sara Johnson</p>
                                        <p class="mb-0">srajhnson@yeti.com</p>
                                    </div>
                                    <div class="ms-auto">
                                        <p class="mb-0">2hr Ago</p>
                                    </div>
                                </div>
                                <p class="text-dark fw-semibold fs-16 mb-2">Join as a full stack developers</p>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">html </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">CSS </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">JavaScript </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">NodeJS </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">ExpressJS </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">ExpressJS </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">Django </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 border px-2 py-1">MySQL </b-badge>
                                <b-badge :variant="null" class="bg-light-subtle text-dark fw-medium fs-12 my-1 border px-2 py-1">
                                    PostgreSQL
                                </b-badge>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-lg-item timeline-item-left">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow-alt shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:document-add-bold-duotone" class="text-warning fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">03</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">23 May 2024</p>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <div class="d-flex justify-content-between">
                                        <p class="text-dark fw-semibold fs-16 mb-2">Started Company Meeting</p>
                                        <p class="mb-0">2hr Ago</p>
                                    </div>
                                    <div class="d-flex align-items-center my-1 flex-wrap gap-1">
                                        <div class="avatar-group">
                                            <div class="avatar">
                                                <img src="@/images/users/avatar-4.jpg" alt="" class="rounded-circle avatar-sm" />
                                            </div>
                                            <div class="avatar">
                                                <img src="@/images/users/avatar-5.jpg" alt="" class="rounded-circle avatar-sm" />
                                            </div>
                                            <div class="avatar">
                                                <img src="@/images/users/avatar-6.jpg" alt="" class="rounded-circle avatar-sm" />
                                            </div>
                                            <div class="avatar">
                                                <img src="@/images/users/avatar-7.jpg" alt="" class="rounded-circle avatar-sm" />
                                            </div>
                                            <div class="avatar">
                                                <div class="avatar-sm">
                                                    <span class="avatar-title bg-dark fs-18 rounded-circle text-white">
                                                        <i class="ti ti-plus"></i>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="fs-14 mb-0 ms-1">+23 Employee Join Meeting</p>
                                    </div>
                                    <p class="text-dark fw-semibold mb-0 mt-3">
                                        Topic : <span class="text-muted fw-medium">New project and admin dashboard</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-lg-item timeline-item-right">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:graph-new-up-bold-duotone" class="text-success fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">04</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">23 May 2024</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="text-dark fw-semibold mb-0">
                                            New Admin Release In Bootstrap
                                            <span class="badge bg-success-subtle text-success fs-11 ms-1 px-2 py-1">New Release</span>
                                        </h4>
                                        <p class="mb-0">3hr Ago</p>
                                    </div>
                                    <p>Get started with our company of web components and interactive elements built on top of Bootstrap.</p>
                                    <div class="timeline-album mb-3">
                                        <a href="javascript: void(0);">
                                            <img src="@/images/small/small-7.jpg" alt="" class="rounded-3" />
                                        </a>
                                        <a href="javascript: void(0);">
                                            <img src="@/images/small/small-4.jpg" alt="" class="rounded-3" />
                                        </a>
                                        <a href="javascript: void(0);">
                                            <img src="@/images/small/small-1.jpg" alt="" class="rounded-3" />
                                        </a>
                                        <a href="javascript: void(0);">
                                            <img src="@/images/small/small-2.jpg" alt="" class="rounded-3" />
                                        </a>
                                    </div>
                                    <a href="#!" class="btn btn-primary btn-sm">Show More</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-lg-item timeline-item-left">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow-alt shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:presentation-graph-bold-duotone" class="text-primary fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">05</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">23 May 2024</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex align-items-center my-3 gap-2">
                                        <img src="@/images/users/avatar-5.jpg" alt="" class="avatar-lg rounded-circle border-light border border-2" />
                                        <div>
                                            <h4 class="text-dark fw-semibold">Assigned to serve as the project's director</h4>
                                            <span class="text-dark"
                                                ><small>by <a href="#!" class="text-primary">John N. Ward.</a></small></span
                                            >
                                        </div>
                                        <div class="ms-auto">
                                            <p class="mb-0">3hr Ago</p>
                                        </div>
                                    </div>
                                    <p class="mb-0">
                                        I've come across your posts and found some favorable deals on your page. I've added a load of products to the
                                        cart and I don't know the payment options you avail. Also, can you enlighten me about any discount
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-show my-3 text-center">
                        <h5 class="time-show-name m-0">Yesterday</h5>
                    </div>

                    <div class="timeline-lg-item timeline-item-right">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:code-circle-bold-duotone" class="text-warning fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">01</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">22 May 2024</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <h4 class="text-dark fw-semibold mb-0">We have achieved 5.6k sales in our themes.</h4>
                                        <p class="mb-0">1day Ago</p>
                                    </div>
                                    <p class="mb-0">
                                        As we celebrate this achievement, we remain focused on our mission to deliver top-notch themes that meet the
                                        evolving needs of our users. We are excited about the future and are dedicated to reaching new heights,
                                        expanding our offerings, and maintaining the high standards that have earned us this success
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="timeline-lg-item timeline-item-left">
                        <div class="timeline-desk">
                            <div class="timeline-box">
                                <span class="arrow-alt shadow-none"></span>
                                <span class="timeline-icon avatar-lg">
                                    <span class="avatar-title bg-light rounded-circle">
                                        <Icon icon="solar:book-bookmark-bold-duotone" class="text-success fs-28" />
                                    </span>
                                </span>
                                <div class="d-flex justify-content-between gap-2">
                                    <h4 class="fw-bold text-dark mb-0">02</h4>
                                    <div class="text-end">
                                        <p class="fw-medium mb-0">22 May 2024</p>
                                    </div>
                                </div>
                                <div class="mt-3">
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="avatar-lg">
                                            <span class="avatar-title bg-light rounded-circle">
                                                <Icon icon="solar:monitor-bold" class="text-primary fs-28" />
                                            </span>
                                        </div>
                                        <div>
                                            <h4 class="text-dark fw-semibold mb-0">Website Launched</h4>
                                            <p class="text-muted fw-medium fs-14 mb-0 mt-1"><span class="text-dark">Name : </span> Ocen</p>
                                        </div>
                                    </div>
                                    <p class="mb-0 mt-2">
                                        Creating a simple Bootstrap website involves using the Bootstrap framework to style and layout your HTML
                                        content.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Icon } from '@iconify/vue';
</script>
