<template>
    <VerticalLayout>
        <PageTitle title="Grid System" subtitle="Base UI" />
        <b-row>
            <b-col cols="12">
                <UICard title="Grid Options" title-class="mb-1">
                    <b-table-simple responsive striped bordered class="mb-0">
                        <b-thead>
                            <b-tr>
                                <b-th></b-th>
                                <b-th class="text-center">
                                    Extra small<br />
                                    <small>&lt;576px</small>
                                </b-th>
                                <b-th class="text-center">
                                    Small<br />
                                    <small>≥576px</small>
                                </b-th>
                                <b-th class="text-center">
                                    Medium<br />
                                    <small>≥768px</small>
                                </b-th>
                                <b-th class="text-center">
                                    Large<br />
                                    <small>≥992px</small>
                                </b-th>
                                <b-th class="text-center">
                                    Extra Large<br />
                                    <small>≥1200px</small>
                                </b-th>
                                <b-th class="text-center">
                                    Extra Large<br />
                                    <small>≥1400px</small>
                                </b-th>
                            </b-tr>
                        </b-thead>
                        <b-tbody>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row">Container <code class="fw-normal">max-width</code></b-th>
                                <b-td>None (auto)</b-td>
                                <b-td>540px</b-td>
                                <b-td>720px</b-td>
                                <b-td>960px</b-td>
                                <b-td>1140px</b-td>
                                <b-td>1320px</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row">Class prefix</b-th>
                                <b-td><code>.col-</code></b-td>
                                <b-td><code>.col-sm-</code></b-td>
                                <b-td><code>.col-md-</code></b-td>
                                <b-td><code>.col-lg-</code></b-td>
                                <b-td><code>.col-xl-</code></b-td>
                                <b-td><code>.col-xxl-</code></b-td>
                            </b-tr>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row"># of columns</b-th>
                                <b-td colspan="6">12</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row">Gutter width</b-th>
                                <b-td colspan="6">1.25rem (0.625rem on left and right)</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row">Custom gutters</b-th>
                                <b-td colspan="6">Yes</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row">Nestable</b-th>
                                <b-td colspan="6">Yes</b-td>
                            </b-tr>
                            <b-tr>
                                <b-th class="text-nowrap" scope="row">Column ordering</b-th>
                                <b-td colspan="6">Yes</b-td>
                            </b-tr>
                        </b-tbody>
                    </b-table-simple>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Grid Example" title-class="mb-1">
                    <div class="grid-structure">
                        <b-row>
                            <b-col lg="12">
                                <div class="grid-container">col-lg-12</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="11">
                                <div class="grid-container">col-lg-11</div>
                            </b-col>
                            <b-col lg="1">
                                <div class="grid-container">col-lg-1</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="10">
                                <div class="grid-container">col-lg-10</div>
                            </b-col>
                            <b-col lg="2">
                                <div class="grid-container">col-lg-2</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="9">
                                <div class="grid-container">col-lg-9</div>
                            </b-col>
                            <b-col lg="3">
                                <div class="grid-container">col-lg-3</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="8">
                                <div class="grid-container">col-lg-8</div>
                            </b-col>
                            <b-col lg="4">
                                <div class="grid-container">col-lg-4</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="7">
                                <div class="grid-container">col-lg-7</div>
                            </b-col>
                            <b-col lg="5">
                                <div class="grid-container">col-lg-5</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="6">
                                <div class="grid-container">col-lg-6</div>
                            </b-col>
                            <b-col lg="6">
                                <div class="grid-container">col-lg-6</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="5">
                                <div class="grid-container">col-lg-5</div>
                            </b-col>
                            <b-col lg="7">
                                <div class="grid-container">col-lg-7</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="4">
                                <div class="grid-container">col-lg-4</div>
                            </b-col>
                            <b-col lg="8">
                                <div class="grid-container">col-lg-8</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="3">
                                <div class="grid-container">col-lg-3</div>
                            </b-col>
                            <b-col lg="9">
                                <div class="grid-container">col-lg-9</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="2">
                                <div class="grid-container">col-lg-2</div>
                            </b-col>
                            <b-col lg="10">
                                <div class="grid-container">col-lg-10</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="1">
                                <div class="grid-container">col-lg-1</div>
                            </b-col>
                            <b-col lg="11">
                                <div class="grid-container">col-lg-11</div>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col lg="2">
                                <div class="grid-container">col-lg-2</div>
                            </b-col>
                            <b-col lg="3">
                                <div class="grid-container">col-lg-3</div>
                            </b-col>
                            <b-col lg="4">
                                <div class="grid-container">col-lg-4</div>
                            </b-col>
                            <b-col lg="2">
                                <div class="grid-container">col-lg-2</div>
                            </b-col>
                            <b-col lg="1">
                                <div class="grid-container">col-lg-1</div>
                            </b-col>
                        </b-row>
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
