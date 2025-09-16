<template>
    <VerticalLayout>
        <PageTitle title="Tooltips" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Examples">
                    <p class="muted mb-0">
                        Tight pants next level keffiyeh <a href="#" v-b-tooltip="'Default tooltip'">you probably</a> haven't heard of them. Photo
                        booth beard raw denim letterpress vegan messenger bag stumptown. Farm-to-table Photo booth beard seitan, mcsweeney's fixie
                        sustainable quinoa 8-bit american apparel <a href="#" v-b-tooltip="'Another tooltip'">have a</a> terry richardson vinyl
                        chambray. Beard stumptown, cardigans banh mi lomo thundercats. Tofu biodiesel williamsburg marfa, four loko mcsweeney's
                        cleanse vegan chambray. A really ironic artisan <a href="#" v-b-tooltip="'Another one here too'">whatever </a> keytar,
                        scenester farm-to-table banksy Austin <a href="#" v-b-tooltip="'The last tip!'">twitter handle</a> freegan cred raw denim
                        single-origin coffee viral.
                    </p>
                </UICard>

                <UICard title="Disabled Elements">
                    <div>
                        <span class="d-inline-block" v-b-tooltip="'Disabled tooltip'">
                            <b-button variant="primary" class="pe-none" type="button" disabled>Disabled button</b-button>
                        </span>
                    </div>
                </UICard>

                <UICard title="Hover Elements">
                    <b-button variant="primary" type="button" v-b-tooltip.hover="'Hover Only, Not a Focus'">Hover</b-button>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Four Directions">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="info" v-b-tooltip.hover.top="'Tooltip on top'">Tooltip on top</b-button>
                        <b-button type="button" variant="info" v-b-tooltip.hover.bottom="'Tooltip on bottom'">Tooltip on bottom</b-button>
                        <b-button type="button" variant="info" v-b-tooltip.hover.left="'Tooltip on left'">Tooltip on left</b-button>
                        <b-button type="button" variant="info" v-b-tooltip.hover.right="'Tooltip on right'">Tooltip on right</b-button>
                    </div>
                </UICard>

                <UICard title="HTML Tags">
                    <b-button type="button" variant="secondary" v-b-tooltip="`<em>Tooltip</em> <u>with</u> <b>HTML</b>`">
                        Tooltip with HTML
                    </b-button>
                </UICard>

                <UICard title="Color Tooltips">
                    <div class="d-flex flex-wrap gap-2">
                        <b-button type="button" variant="primary" id="tooltip"> Primary tooltip </b-button>
                        <b-tooltip target="tooltip" class="tooltip-primary">This top tooltip is themed via CSS variables.</b-tooltip>
                        <b-button type="button" variant="danger" id="tooltip1"> Danger tooltip </b-button>
                        <b-tooltip target="tooltip1" class="tooltip-danger">This top tooltip is themed via CSS variables.</b-tooltip>
                        <b-button type="button" variant="info" id="tooltip2"> Info tooltip </b-button>
                        <b-tooltip target="tooltip2" class="tooltip-info">This top tooltip is themed via CSS variables.</b-tooltip>
                        <b-button type="button" variant="success" id="tooltip3"> Success tooltip </b-button>
                        <b-tooltip target="tooltip3" class="tooltip-success">This top tooltip is themed via CSS variables.</b-tooltip>
                        <b-button type="button" variant="secondary" id="tooltip4"> Secondary tooltip </b-button>
                        <b-tooltip target="tooltip4" class="tooltip-secondary">This top tooltip is themed via CSS variables.</b-tooltip>
                        <b-button type="button" variant="warning" id="tooltip5"> Warning tooltip </b-button>
                        <b-tooltip target="tooltip5" class="tooltip-warning">This top tooltip is themed via CSS variables.</b-tooltip>
                        <b-button type="button" variant="dark" id="tooltip6"> Dark tooltip </b-button>
                        <b-tooltip target="tooltip6" class="tooltip-dark">This top tooltip is themed via CSS variables.</b-tooltip>
                    </div>
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
