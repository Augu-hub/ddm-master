<template>
    <VerticalLayout>
        <PageTitle title="Utilities" subtitle="Base UI" />

        <b-row>
            <b-col xl="6">
                <UICard title="Background Color">
                    <div class="bg-primary mb-2 p-2 text-white">.bg-primary</div>
                    <div class="bg-secondary mb-2 p-2 text-white">.bg-secondary</div>
                    <div class="bg-success mb-2 p-2 text-white">.bg-success</div>
                    <div class="bg-danger mb-2 p-2 text-white">.bg-danger</div>
                    <div class="bg-warning text-dark mb-2 p-2">.bg-warning</div>
                    <div class="bg-info text-dark mb-2 p-2">.bg-info</div>
                    <div class="bg-light text-dark mb-2 p-2">.bg-light</div>
                    <div class="bg-dark mb-2 p-2">.bg-dark</div>
                    <div class="bg-body text-dark mb-2 p-2">.bg-body</div>
                    <div class="bg-body-secondary text-dark mb-2 p-2">.bg-body-secondary</div>
                    <div class="bg-body-tertiary text-dark mb-2 p-2">.bg-body-tertiary</div>
                    <div class="mb-2 bg-white p-2">.bg-white</div>
                    <div class="mb-2 bg-black p-2 text-white">.bg-black</div>
                    <div class="text-dark bg-transparent p-2">.bg-transparent</div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Background Gradient Color">
                    <div class="bg-primary bg-gradient mb-2 p-2 text-white">.bg-gradient.bg-primary</div>
                    <div class="bg-secondary bg-gradient mb-2 p-2 text-white">.bg-secondary.bg-gradient</div>
                    <div class="bg-success bg-gradient mb-2 p-2 text-white">.bg-success.bg-gradient</div>
                    <div class="bg-danger bg-gradient mb-2 p-2 text-white">.bg-danger.bg-gradient</div>
                    <div class="bg-warning bg-gradient text-dark mb-2 p-2">.bg-warning.bg-gradient</div>
                    <div class="bg-info bg-gradient text-dark mb-2 p-2">.bg-info.bg-gradient</div>
                    <div class="bg-light bg-gradient text-dark mb-2 p-2">.bg-light.bg-gradient</div>
                    <div class="bg-dark bg-gradient mb-2 p-2 text-white">.bg-dark.bg-gradient</div>
                    <div class="bg-gradient mb-2 bg-black p-2 text-white">.bg-black.bg-gradient</div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Soft Background">
                    <b-row>
                        <b-col cols="12">
                            <div class="d-flex flex-column gap-2">
                                <div class="bg-primary-subtle p-2"><code class="text-primary-emphasis">.bg-primary-subtle</code></div>
                                <div class="bg-secondary-subtle p-2"><code class="text-secondary-emphasis">.bg-secondary-subtle </code></div>
                                <div class="bg-success-subtle p-2"><code class="text-success-emphasis">.bg-success-subtle</code></div>
                                <div class="bg-danger-subtle p-2"><code class="text-danger-emphasis">.bg-danger-subtle</code></div>
                                <div class="bg-warning-subtle p-2"><code class="text-warning-emphasis">.bg-warning-subtle</code></div>
                                <div class="bg-info-subtle p-2"><code class="text-info-emphasis">.bg-info-subtle</code></div>
                                <div class="bg-light-subtle p-2"><code class="text-light-emphasis">.bg-light-subtle</code></div>
                                <div class="bg-dark-subtle p-2"><code class="text-dark-emphasis">.bg-dark-subtle</code></div>
                            </div>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Color & Background">
                    <div class="d-flex flex-column gap-2">
                        <div class="text-bg-primary p-2">Primary with contrasting color (.text-bg-primary)</div>
                        <div class="text-bg-secondary p-2">Secondary with contrasting color (.text-bg-secondary)</div>
                        <div class="text-bg-success p-2">Success with contrasting color (.text-bg-success)</div>
                        <div class="text-bg-danger p-2">Danger with contrasting color (.text-bg-danger)</div>
                        <div class="text-bg-warning p-2">Warning with contrasting color (.text-bg-warning)</div>
                        <div class="text-bg-info p-2">Info with contrasting color (.text-bg-info)</div>
                        <div class="text-bg-light p-2">Light with contrasting color (.text-bg-light)</div>
                        <div class="text-bg-dark p-2">Dark with contrasting color (.text-bg-dark)</div>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Colored links">
                    <div class="d-flex flex-column gap-2">
                        <a href="#" class="link-primary">Primary link</a>
                        <a href="#" class="link-secondary">Secondary link</a>
                        <a href="#" class="link-success">Success link</a>
                        <a href="#" class="link-danger">Danger link</a>
                        <a href="#" class="link-warning">Warning link</a>
                        <a href="#" class="link-info">Info link</a>
                        <a href="#" class="link-light">Light link</a>
                        <a href="#" class="link-dark">Dark link</a>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Background Opacity">
                    <div class="bg-primary p-2 text-white">This is default primary background</div>
                    <div class="bg-primary bg-opacity-75 p-2 text-white">This is 75% opacity primary background</div>
                    <div class="bg-primary text-dark bg-opacity-50 p-2">This is 50% opacity primary background</div>
                    <div class="bg-primary text-dark bg-opacity-25 p-2">This is 25% opacity primary background</div>
                    <div class="bg-primary text-dark bg-opacity-10 p-2">This is 10% opacity success background</div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Text Color">
                    <b-row>
                        <b-col md="6">
                            <p class="text-primary">.text-primary</p>
                            <p class="text-primary-emphasis">.text-primary-emphasis</p>
                            <p class="text-secondary">.text-secondary</p>
                            <p class="text-secondary-emphasis">.text-secondary-emphasis</p>
                            <p class="text-success">.text-success</p>
                            <p class="text-success-emphasis">.text-success-emphasis</p>
                            <p class="text-danger">.text-danger</p>
                            <p class="text-danger-emphasis">.text-danger-emphasis</p>
                            <p class="text-warning">.text-warning</p>
                            <p class="text-warning-emphasis">.text-warning-emphasis</p>
                            <p class="text-info">.text-info</p>
                            <p class="text-info-emphasis">.text-info-emphasis</p>
                            <p class="text-light bg-dark">.text-light</p>
                            <p class="text-light-emphasis">.text-light-emphasis</p>
                        </b-col>
                        <b-col md="6">
                            <p class="text-dark">.text-dark</p>
                            <p class="text-dark-emphasis">.text-dark-emphasis</p>
                            <p class="text-muted">.text-muted</p>
                            <p class="text-body">.text-body</p>
                            <p class="text-body-emphasis">.text-body-emphasis</p>
                            <p class="text-body-secondary">.text-body-secondary</p>
                            <p class="text-body-tertiary">.text-body-tertiary</p>
                            <p class="text-black">.text-black</p>
                            <p class="bg-dark text-white">.text-white</p>
                            <p class="text-black-50">.text-black-50</p>
                            <p class="text-white-50 bg-dark">.text-white-50</p>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Text Opacity Color">
                    <div class="text-primary">This is default primary text</div>
                    <div class="text-primary text-opacity-75">This is 75% opacity primary text</div>
                    <div class="text-primary text-opacity-50">This is 50% opacity primary text</div>
                    <div class="text-primary text-opacity-25">This is 25% opacity primary text</div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Opacity">
                    <div class="d-flex flex-wrap gap-2">
                        <div class="bg-primary text-light fw-bold rounded p-2 opacity-100">100%</div>
                        <div class="bg-primary text-light fw-bold rounded p-2 opacity-75">75%</div>
                        <div class="bg-primary text-light fw-bold rounded p-2 opacity-50">50%</div>
                        <div class="bg-primary text-light fw-bold rounded p-2 opacity-25">25%</div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Additive(Add) Border">
                    <div class="d-flex align-items-start flex-wrap gap-4">
                        <div class="text-center">
                            <div class="avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-top avatar-md bg-light bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-end avatar-md bg-light bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-bottom avatar-md bg-light bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-start avatar-md bg-light bg-opacity-50"></div>
                        </div>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Subtractive(Remove) Border">
                    <div class="d-flex align-items-start flex-wrap gap-4">
                        <div class="text-center">
                            <div class="avatar-md bg-light border border-0 bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-top-0 avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-end-0 avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-bottom-0 avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-start-0 avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Border Color">
                    <div class="d-flex align-items-start flex-wrap gap-2">
                        <div class="text-center">
                            <div class="border-primary avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-secondary avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-success avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-danger avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-warning avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-info avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-light avatar-md border"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-dark avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                    </div>
                </UICard>

                <UICard title="Border Width Size">
                    <b-card-body>
                        <div class="d-flex align-items-start flex-wrap gap-2">
                            <div class="text-center">
                                <div class="avatar-md bg-light border-1 border bg-opacity-50"></div>
                            </div>
                            <div class="text-center">
                                <div class="avatar-md bg-light border border-2 bg-opacity-50"></div>
                            </div>
                            <div class="text-center">
                                <div class="avatar-md bg-light border-3 border bg-opacity-50"></div>
                            </div>
                            <div class="text-center">
                                <div class="avatar-md bg-light border border-4 bg-opacity-50"></div>
                            </div>
                            <div class="text-center">
                                <div class="avatar-md bg-light border-5 border bg-opacity-50"></div>
                            </div>
                        </div>
                    </b-card-body>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Border Opacity">
                    <div class="border-success mb-2 border p-2">This is default success border</div>
                    <div class="border-success mb-2 border border-opacity-75 p-2">This is 75% opacity success border</div>
                    <div class="border-success mb-2 border border-opacity-50 p-2">This is 50% opacity success border</div>
                    <div class="border-success mb-2 border border-opacity-25 p-2">This is 25% opacity success border</div>
                    <div class="border-success border border-opacity-10 p-2">This is 10% opacity success border</div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Border Subtle Color">
                    <div class="d-flex align-items-start flex-wrap gap-2">
                        <div class="text-center">
                            <div class="border-primary-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-secondary-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-success-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-danger-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-warning-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-info-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-light-subtle avatar-md border"></div>
                        </div>
                        <div class="text-center">
                            <div class="border-dark-subtle avatar-md bg-light border bg-opacity-50"></div>
                        </div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Border Radius">
                    <div class="d-flex align-items-start flex-wrap gap-2">
                        <img :src="avatar2" class="avatar-lg rounded" alt="rounded" />
                        <img :src="avatar2" class="avatar-lg rounded-top" alt="rounded-top" />
                        <img :src="avatar2" class="avatar-lg rounded-end" alt="rounded-end" />
                        <img :src="avatar2" class="avatar-lg rounded-bottom" alt="rounded-bottom" />
                        <img :src="avatar2" class="avatar-lg rounded-start" alt="rounded-start" />
                        <img :src="avatar2" class="avatar-lg rounded-circle" alt="rounded-circle" />
                        <img :src="small2" class="avatar-lg rounded-pill w-auto" alt="rounded-pill" />
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Border Radius Size">
                    <div class="d-flex align-items-start flex-wrap gap-2">
                        <img :src="avatar4" class="avatar-lg rounded-0" alt="rounded-0" />
                        <img :src="avatar4" class="avatar-lg rounded-1" alt="rounded-1" />
                        <img :src="avatar4" class="avatar-lg rounded-2" alt="rounded-2" />
                        <img :src="avatar4" class="avatar-lg rounded-3" alt="rounded-3" />
                        <img :src="avatar4" class="avatar-lg rounded-4" alt="rounded-4" />
                        <img :src="avatar4" class="avatar-lg rounded-5" alt="rounded-5" />
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Text Selection">
                    <p class="user-select-all">This paragraph will be entirely selected when clicked by the user.</p>
                    <p class="user-select-auto">This paragraph has default select behavior.</p>
                    <p class="user-select-none mb-0">This paragraph will not be selectable when clicked by the user.</p>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Pointer Events">
                    <p><a href="#" class="pe-none" tabindex="-1" aria-disabled="true">This link</a> can not be clicked.</p>
                    <p><a href="#" class="pe-auto">This link</a> can be clicked (this is default behavior).</p>
                    <p class="pe-none">
                        <a href="#" tabindex="-1" aria-disabled="true">This link</a>
                        can not be clicked because the
                        <code>pointer-events</code> property is inherited from its parent. However, <a href="#" class="pe-auto">this link</a> has a
                        <code>pe-auto</code> class and can be clicked.
                    </p>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Overflow">
                    <div class="d-flex flex-wrap gap-4">
                        <div class="bg-light overflow-auto p-3" style="max-width: 260px; max-height: 100px">
                            This is an example of using <code>.overflow-auto</code> on an element with set width and height dimensions. By design,
                            this content will vertically scroll.
                        </div>
                        <div class="bg-light overflow-hidden p-3" style="max-width: 260px; max-height: 100px">
                            This is an example of using <code>.overflow-hidden</code> on an element with set width and height dimensions.
                        </div>
                        <div class="bg-light overflow-visible p-3" style="max-width: 260px; max-height: 100px">
                            This is an example of using <code>.overflow-visible</code> on an element with set width and height dimensions.
                        </div>
                        <div class="bg-light overflow-scroll p-3" style="max-width: 260px; max-height: 100px">
                            This is an example of using <code>.overflow-scroll</code> on an element with set width and height dimensions.
                        </div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Position in Arrange">
                    <div class="position-relative bg-light m-3 rounded border bg-opacity-50 p-5" style="height: 180px">
                        <div class="position-absolute avatar-xs bg-dark start-0 top-0 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark end-0 top-0 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark start-50 top-50 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark end-50 bottom-50 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark bottom-0 start-0 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark bottom-0 end-0 rounded"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Position in Center">
                    <div class="position-relative bg-light m-3 rounded border bg-opacity-50" style="height: 180px">
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-0 top-0 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-50 top-0 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-100 top-0 rounded"></div>

                        <div class="position-absolute translate-middle avatar-xs bg-dark top-50 start-0 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-50 top-50 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-100 top-50 rounded"></div>

                        <div class="position-absolute translate-middle avatar-xs bg-dark top-100 start-0 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-50 top-100 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-100 top-100 rounded"></div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Position in Axis">
                    <div class="position-relative bg-light m-3 rounded border" style="height: 180px">
                        <div class="position-absolute avatar-xs bg-dark start-0 top-0 rounded"></div>
                        <div class="position-absolute translate-middle-x avatar-xs bg-dark start-50 top-0 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark end-0 top-0 rounded"></div>

                        <div class="position-absolute translate-middle-y avatar-xs bg-dark top-50 start-0 rounded"></div>
                        <div class="position-absolute translate-middle avatar-xs bg-dark start-50 top-50 rounded"></div>
                        <div class="position-absolute translate-middle-y avatar-xs bg-dark top-50 end-0 rounded"></div>

                        <div class="position-absolute avatar-xs bg-dark bottom-0 start-0 rounded"></div>
                        <div class="position-absolute translate-middle-x avatar-xs bg-dark start-50 bottom-0 rounded"></div>
                        <div class="position-absolute avatar-xs bg-dark bottom-0 end-0 rounded"></div>
                    </div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Shadows">
                    <div class="bg-light mb-2 rounded p-2 shadow-none">No shadow</div>
                    <div class="mb-2 rounded p-2 shadow-sm">Small shadow</div>
                    <div class="mb-2 rounded p-2 shadow">Regular shadow</div>
                    <div class="mb-2 rounded p-2 shadow-lg">Larger shadow</div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Width">
                    <div class="bg-light w-25 p-2">Width 25%</div>

                    <div class="bg-light w-50 p-2">Width 50%</div>

                    <div class="bg-light w-75 p-2">Width 75%</div>

                    <div class="bg-light w-100 p-2">Width 100%</div>

                    <div class="bg-light w-auto p-2">Width auto</div>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Height">
                    <div class="d-flex align-items-start flex-wrap gap-3" style="height: 255px">
                        <div class="bg-light h-25 p-2">Height25%</div>

                        <div class="bg-light h-50 p-2">Height50%</div>

                        <div class="bg-light h-75 p-2">Height75%</div>

                        <div class="bg-light h-100 p-2">Height100%</div>

                        <div class="bg-light h-auto p-2">Height auto</div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Object Fit">
                    <div class="d-flex align-items-start flex-wrap gap-3 text-center">
                        <div>
                            <img :src="small1" class="object-fit-contain avatar-xl rounded border" alt="..." />
                            <p class="mb-0 mt-1"><code class="user-select-all">.object-fit-contain</code></p>
                        </div>
                        <div>
                            <img :src="small1" class="object-fit-cover avatar-xl rounded border" alt="..." />
                            <p class="mb-0 mt-1"><code class="user-select-all">.object-fit-cover</code></p>
                        </div>
                        <div>
                            <img :src="small1" class="object-fit-fill avatar-xl rounded border" alt="..." />
                            <p class="mb-0 mt-1"><code class="user-select-all">.object-fit-fill</code></p>
                        </div>
                        <div>
                            <img :src="small1" class="object-fit-scale avatar-xl rounded border" alt="..." />
                            <p class="mb-0 mt-1"><code class="user-select-all">.object-fit-scale</code></p>
                        </div>
                        <div>
                            <img :src="small1" class="object-fit-none avatar-xl rounded border" alt="..." />
                            <p class="mb-0 mt-1"><code class="user-select-all">.object-fit-none</code></p>
                        </div>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="12">
                <UICard title="Z-index">
                    <div class="position-relative" style="height: 220px; z-index: 1">
                        <div class="position-absolute rounded-3 bg-primary-subtle z-3 p-5"></div>
                        <div class="position-absolute rounded-3 bg-success-subtle z-2 m-2 p-5"></div>
                        <div class="position-absolute rounded-3 bg-secondary-subtle z-1 m-3 p-5"></div>
                        <div class="position-absolute rounded-3 bg-danger-subtle z-0 m-4 p-5"></div>
                        <div class="z-n1 position-absolute rounded-3 bg-info-subtle m-5 p-5"></div>
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import VerticalLayout from '@/layouts/VerticalLayout.vue';

import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import small1 from '@/images/small/small-1.jpg';
import small2 from '@/images/small/small-2.jpg';
import avatar2 from '@/images/users/avatar-2.jpg';
import avatar4 from '@/images/users/avatar-4.jpg';
</script>
