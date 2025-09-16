<template>
    <VerticalLayout>
        <PageTitle title="Popovers" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Simple Popover">
                    <b-button variant="danger" id="simple-popover"> Click to toggle popover </b-button>
                    <b-popover target="simple-popover" placement="right">
                        <template #title>Popover title</template>
                        And here's some amazing content. It's very engaging. Right?
                    </b-popover>
                </UICard>

                <UICard title="Dismiss on Next Click">
                    <b-button variant="success" id="dismissible-popover"> Dismissible popover </b-button>
                    <b-popover target="dismissible-popover" placement="right">
                        <template #title>Dismissible popover</template>
                        And here's some amazing content. It's very engaging. Right?
                    </b-popover>
                </UICard>

                <UICard title="Hover">
                    <b-button variant="dark" id="hover-popover"> Please Hover Me </b-button>
                    <b-popover target="hover-popover" placement="right" triggers="hover">
                        <template #title>Ohh Wow !</template>
                        And here's some amazing content. It's very engaging. Right?
                    </b-popover>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Four Directions">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button variant="primary" v-b-popover.click.top="`Vivamus sagittis lacus vel augue laoreet rutrum faucibus.`">
                            Popover on top
                        </b-button>
                        <b-button variant="primary" v-b-popover.click.bottom="`Vivamus sagittis lacus vel augue laoreet rutrum faucibus.`">
                            Popover on bottom
                        </b-button>
                        <b-button variant="primary" v-b-popover.click.right="`Vivamus sagittis lacus vel augue laoreet rutrum faucibus.`">
                            Popover on right
                        </b-button>
                        <b-button variant="primary" v-b-popover.click.left="`Vivamus sagittis lacus vel augue laoreet rutrum faucibus.`">
                            Popover on left
                        </b-button>
                    </div>
                </UICard>

                <UICard title="Custom Popovers">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button variant="primary" id="popover-1"> Primary popover </b-button>
                        <b-popover target="popover-1" custom-class="popover-primary" placement="right">
                            <template #title>Primary popover</template>
                            This popover is themed via CSS variables.
                        </b-popover>

                        <b-button variant="success" id="popover-2"> Success popover </b-button>
                        <b-popover target="popover-2" custom-class="popover-success" placement="right">
                            <template #title>Success popover</template>
                            This popover is themed via CSS variables.
                        </b-popover>

                        <b-button variant="danger" id="popover-3"> Danger popover </b-button>
                        <b-popover target="popover-3" custom-class="popover-danger" placement="right">
                            <template #title>Danger popover</template>
                            This popover is themed via CSS variables.
                        </b-popover>

                        <b-button variant="info" id="popover-4"> Info popover</b-button>
                        <b-popover target="popover-4" custom-class="popover-info" placement="right">
                            <template #title>Info popover</template>
                            This popover is themed via CSS variables.
                        </b-popover>

                        <b-button variant="dark" id="popover-5"> Dark popover </b-button>
                        <b-popover target="popover-5" custom-class="popover-dark" placement="right">
                            <template #title>Dark popover</template>
                            This popover is themed via CSS variables.
                        </b-popover>

                        <b-button variant="secondary" id="popover-6"> Secondary popover</b-button>
                        <b-popover target="popover-6" custom-class="popover-secondary" placement="right">
                            <template #title>Secondary popover</template>
                            This popover is themed via CSS variables.
                        </b-popover>
                    </div>
                </UICard>

                <UICard title="Disabled Elements">
                    <span class="d-inline-block" v-b-popover.hover="`Disabled popover`">
                        <b-button variant="primary" style="pointer-events: none" disabled> Disabled button </b-button>
                    </span>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
