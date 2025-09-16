<template>
    <VerticalLayout>
        <PageTitle title="Accordions" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Default Accordions">
                    <b-accordion id="accordionExample">
                        <b-accordion-item title="Accordion Item #1" visible>
                            <strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #2">
                            <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #3">
                            <strong>This is the third item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                    </b-accordion>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Flush Accordions" title-class="mb-0">
                    <b-accordion id="accordionFlushExample" flush>
                        <b-accordion-item title="Accordion Item #1" visible>
                            <p>
                                Placeholder content for this accordion, which is intended to demonstrate the <code>.accordion-flush</code> class. This
                                is the first item's accordion body.
                            </p>
                            <b-button type="button" size="sm" variant="primary">Click Me</b-button>
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #2">
                            Placeholder content for this accordion, which is intended to demonstrate the
                            <code>.accordion-flush</code> class. This is the second item's accordion body. Let's imagine this being filled with some
                            actual content.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #3">
                            Placeholder content for this accordion, which is intended to demonstrate the
                            <code>.accordion-flush</code> class. This is the third item's accordion body. Nothing more exciting happening here in
                            terms of content, but just filling up the space to make it look, at least at first glance, a bit more representative of
                            how this would look in a real-world application.
                        </b-accordion-item>
                    </b-accordion>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Bordered Accordions">
                    <b-accordion id="BorderedaccordionExample" class="accordion-bordered">
                        <b-accordion-item title="Accordion Item #1" visible>
                            <strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #2">
                            <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #3">
                            <strong>This is the third item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                    </b-accordion>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Custom Icon Accordion">
                    <b-accordion id="CustomIconaccordionExample" class="accordion-bordered accordion-custom-icon accordion-arrow-none">
                        <b-accordion-item title="Accordion Item #1" visible>
                            <template #title>
                                Accordion item with tabler icons
                                <i class="ti ti-plus accordion-icon accordion-icon-on"></i>
                                <i class="ti ti-minus accordion-icon accordion-icon-off"></i>
                            </template>
                            <strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #2">
                            <template #title>
                                Accordion item with lucid icons
                                <PlusCircleIcon class="accordion-icon accordion-icon-on avatar-xxs me-n1" />
                                <MinusCircleIcon class="accordion-icon accordion-icon-off avatar-xxs me-n1" />
                            </template>
                            <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #3">
                            <template #title>
                                Accordion item with solar duotone icons
                                <Icon icon="solar:add-square-bold-duotone" class="accordion-icon text-secondary accordion-icon-on fs-24 me-n2" />
                                <Icon icon="solar:minus-square-bold-duotone" class="accordion-icon text-danger accordion-icon-off fs-24 me-n2" />
                            </template>
                            <strong>This is the third item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                    </b-accordion>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Always Open Accordions">
                    <b-accordion id="accordionPanelsStayOpenExample" free>
                        <b-accordion-item title="Accordion Item #1" visible>
                            <strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #2">
                            <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #3">
                            <strong>This is the third item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                    </b-accordion>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Accordion Without Arrow">
                    <b-accordion id="withoutarrowaccordionExample" class="accordion-arrow-none">
                        <b-accordion-item title="Accordion Item #1" visible>
                            <strong>This is the first item's accordion body.</strong> It is shown by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #2">
                            <strong>This is the second item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                        <b-accordion-item title="Accordion Item #3">
                            <strong>This is the third item's accordion body.</strong> It is hidden by default, until the collapse plugin adds the
                            appropriate classes that we use to style each element. These classes control the overall appearance, as well as the
                            showing and hiding via CSS transitions. You can modify any of this with custom CSS or overriding our default variables.
                            It's also worth noting that just about any HTML can go within the <code>.accordion-body</code>, though the transition does
                            limit overflow.
                        </b-accordion-item>
                    </b-accordion>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Icon } from '@iconify/vue';
import { MinusCircleIcon, PlusCircleIcon } from 'lucide-vue-next';
</script>
