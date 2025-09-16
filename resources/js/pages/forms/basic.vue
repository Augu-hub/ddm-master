<template>
    <VerticalLayout>
        <PageTitle title="Form Elements" subtitle="Forms" />
        <b-row>
            <b-col cols="12">
                <UICard title="Input Types">
                    <b-row>
                        <b-col lg="6">
                            <b-form>
                                <b-form-group label="Text" label-for="simpleinput" class="mb-3">
                                    <b-form-input id="simpleinput" />
                                </b-form-group>

                                <b-form-group label="Email" label-for="example-email" class="mb-3">
                                    <b-form-input type="email" id="example-email" placeholder="Email" />
                                </b-form-group>

                                <b-form-group label="Password" label-for="example-password" class="mb-3">
                                    <b-form-input type="password" id="example-password" model-value="password" />
                                </b-form-group>

                                <b-form-group label="Placeholder" label-for="example-palaceholder" class="mb-3">
                                    <b-form-input type="text" id="example-palaceholder" placeholder="placeholder" />
                                </b-form-group>

                                <b-form-group label="Text area" label-for="example-textarea" class="mb-3">
                                    <b-form-textarea rows="5" type="text" id="example-textarea" placeholder="placeholder" />
                                </b-form-group>

                                <b-form-group label="Readonly" label-for="example-readonly" class="mb-3">
                                    <b-form-input type="text" id="example-readonly" model-value="Readonly value" readonly />
                                </b-form-group>

                                <b-form-group label="Disabled" label-for="example-disable" class="mb-3">
                                    <b-form-input type="text" id="example-disable" model-value="Disabled value" disabled />
                                </b-form-group>

                                <b-form-group label="Static control" label-for="example-static" class="mb-3">
                                    <b-form-input type="text" class="form-control-plaintext" id="example-static" model-value="email@example.com" />
                                </b-form-group>

                                <b-form-group label="Helping text" label-for="example-helping">
                                    <b-form-input type="text" id="example-helping" placeholder="Helping text" />
                                    <span class="help-block"
                                        ><small>A block of help text that breaks onto a new line and may extend beyond one line.</small></span
                                    >
                                </b-form-group>
                            </b-form>
                        </b-col>

                        <b-col lg="6">
                            <b-form>
                                <b-form-group label="Input Select" label-for="example-input-select" class="mb-3">
                                    <b-form-select :model-value="1">
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </b-form-select>
                                </b-form-group>

                                <b-form-group label="Multiple Select" label-for="example-multiselect" class="mb-3">
                                    <b-form-select multiple>
                                        <option value="1">1</option>
                                        <option value="2">2</option>
                                        <option value="3">3</option>
                                        <option value="4">4</option>
                                        <option value="5">5</option>
                                    </b-form-select>
                                </b-form-group>

                                <div class="mb-3">
                                    <b-form-file v-model="file" label="Default file input" />
                                </div>

                                <b-form-group label="Date" label-for="example-date" class="mb-3">
                                    <b-form-input type="date" id="example-date" />
                                </b-form-group>

                                <b-form-group label="Month" label-for="example-month" class="mb-3">
                                    <b-form-input type="month" id="example-month" />
                                </b-form-group>

                                <b-form-group label="Time" label-for="example-time" class="mb-3">
                                    <b-form-input type="time" id="example-time" />
                                </b-form-group>

                                <b-form-group label="Week" label-for="example-week" class="mb-3">
                                    <b-form-input type="week" id="example-week" />
                                </b-form-group>

                                <b-form-group label="Number" label-for="example-number" class="mb-3">
                                    <b-form-input type="number" id="example-number" />
                                </b-form-group>

                                <b-form-group label="Color" label-for="example-color" class="mb-3">
                                    <b-form-input type="color" id="example-color" model-value="#727cf5" />
                                </b-form-group>

                                <b-form-group label="Range" label-for="example-range" class="mb-0">
                                    <b-form-input type="range" id="example-range" min="0" max="100" />
                                </b-form-group>
                            </b-form>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Floating Labels">
                    <b-row>
                        <b-col lg="6">
                            <h5 class="mb-3">Example</h5>
                            <b-form-floating-label label="Email address" label-for="floatingInput" class="mb-3">
                                <b-form-input id="floatingInput" type="email" placeholder="name@example.com" />
                            </b-form-floating-label>

                            <b-form-floating-label label="Password" label-for="floatingPassword">
                                <b-form-input id="floatingPassword" type="password" placeholder="password" />
                            </b-form-floating-label>

                            <h5 class="mb-3 mt-4">Textareas</h5>
                            <b-form-floating-label label="Comments" label-for="floatingTextarea">
                                <b-form-textarea id="floatingTextarea" placeholder="Leave a comment here" style="height: 100px" />
                            </b-form-floating-label>
                        </b-col>

                        <b-col lg="6">
                            <h5 class="mb-3">Selects</h5>
                            <b-form-floating-label label="Works with selects">
                                <b-form-select :model-value="1">
                                    <option value="1">Open this select menu</option>
                                    <option value="2">One</option>
                                    <option value="3">Two</option>
                                    <option value="4">Three</option>
                                </b-form-select>
                            </b-form-floating-label>

                            <h5 class="mb-3 mt-4">Layout</h5>
                            <b-row class="g-2">
                                <div class="col-md">
                                    <b-form-floating-label label="Email address">
                                        <b-form-input
                                            id="floatingPassword"
                                            type="email"
                                            placeholder="name@example.com"
                                            model-value="mdo@example.com"
                                        />
                                    </b-form-floating-label>
                                </div>
                                <div class="col-md">
                                    <b-form-floating-label label="Works with selects">
                                        <b-form-select :model-value="1">
                                            <option value="1">Open this select menu</option>
                                            <option value="2">One</option>
                                            <option value="3">Two</option>
                                            <option value="4">Three</option>
                                        </b-form-select>
                                    </b-form-floating-label>
                                </div>
                            </b-row>
                        </b-col>
                    </b-row>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <UICard title="Select">
                    <b-form-select :model-value="1" class="mb-3">
                        <option value="1">Open this select menu</option>
                        <option value="2">One</option>
                        <option value="3">Two</option>
                        <option value="4">Three</option>
                    </b-form-select>

                    <b-form-select :model-value="1" size="lg" class="mb-3">
                        <option value="1">Open this select menu</option>
                        <option value="2">One</option>
                        <option value="3">Two</option>
                        <option value="4">Three</option>
                    </b-form-select>

                    <b-form-select :model-value="1" size="sm" class="mb-3">
                        <option value="1">Open this select menu</option>
                        <option value="2">One</option>
                        <option value="3">Two</option>
                        <option value="4">Three</option>
                    </b-form-select>

                    <b-input-group class="mb-3">
                        <template #prepend>
                            <label class="input-group-text" for="inputGroupSelect01">Options</label>
                        </template>
                        <b-form-select :model-value="1">
                            <option value="1">Choose...</option>
                            <option value="2">One</option>
                            <option value="3">Two</option>
                            <option value="4">Three</option>
                        </b-form-select>
                    </b-input-group>

                    <b-input-group>
                        <b-form-select :model-value="1">
                            <option value="1">Choose...</option>
                            <option value="2">One</option>
                            <option value="3">Two</option>
                            <option value="4">Three</option>
                        </b-form-select>
                        <template #append>
                            <b-button variant="outline-secondary" type="button">Button</b-button>
                        </template>
                    </b-input-group>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Switches">
                    <b-form-checkbox switch>Toggle this switch element</b-form-checkbox>
                    <b-form-checkbox switch disabled class="mt-1">Disabled switch element</b-form-checkbox>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <UICard title="Checkboxes">
                    <div class="mt-3">
                        <b-form-checkbox class="mb-1"> Check this custom checkbox </b-form-checkbox>
                        <b-form-checkbox> Check this custom checkbox </b-form-checkbox>
                    </div>

                    <h6 class="fs-15 mt-3">Inline</h6>

                    <div class="mt-2">
                        <b-form-checkbox inline> Check this custom checkbox </b-form-checkbox>
                        <b-form-checkbox inline> Check this custom checkbox </b-form-checkbox>
                    </div>

                    <h6 class="fs-15 mt-3">Disabled</h6>

                    <div class="mt-2">
                        <b-form-checkbox inline disabled checked> Check this custom checkbox </b-form-checkbox>
                        <b-form-checkbox inline disabled> Check this custom checkbox </b-form-checkbox>
                    </div>
                    <h6 class="fs-15 mt-3">Colors</h6>
                    <b-form-checkbox class="mb-2" checked> Default Checkbox </b-form-checkbox>
                    <b-form-checkbox class="form-checkbox-success mb-2" checked> Success Checkbox </b-form-checkbox>
                    <b-form-checkbox class="form-checkbox-info mb-2" checked> Info Checkbox </b-form-checkbox>
                    <b-form-checkbox class="form-checkbox-secondary mb-2" checked> Secondary Checkbox </b-form-checkbox>
                    <b-form-checkbox class="form-checkbox-warning mb-2" checked> Warning Checkbox </b-form-checkbox>
                    <b-form-checkbox class="form-checkbox-danger mb-2" checked> Danger Checkbox </b-form-checkbox>
                    <b-form-checkbox class="form-checkbox-dark" checked> Dark Checkbox </b-form-checkbox>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Radios">
                    <b-form-group class="mt-3">
                        <b-form-radio class="mb-1" name="radioDefault" value="1">Toggle this custom radio</b-form-radio>
                        <b-form-radio name="radioDefault" value="2">Or toggle this other custom radio</b-form-radio>
                    </b-form-group>

                    <h6 class="fs-15 mt-3">Inline</h6>

                    <b-form-group class="mt-2">
                        <b-form-radio name="customRadio1" value="1" inline>Toggle this custom radio</b-form-radio>
                        <b-form-radio name="customRadio1" value="2" inline>Or toggle this other custom radio</b-form-radio>
                    </b-form-group>

                    <h6 class="fs-15 mt-3">Disabled</h6>
                    <b-form-group class="mt-2">
                        <b-form-radio name="customRadio2" value="1" inline disabled>Toggle this custom radio</b-form-radio>
                        <b-form-radio name="customRadio2" value="2" inline disabled>Or toggle this other custom radio</b-form-radio>
                    </b-form-group>

                    <h6 class="fs-15 mt-3">Colors</h6>

                    <div class="mb-2">
                        <b-form-radio id="customRadiocolor1" checked> Default Radio </b-form-radio>
                    </div>
                    <div class="form-radio-success mb-2">
                        <b-form-radio id="customRadiocolor2" checked> Success Radio </b-form-radio>
                    </div>
                    <div class="form-radio-info mb-2">
                        <b-form-radio id="customRadiocolor3" checked> Info Radio </b-form-radio>
                    </div>
                    <div class="form-radio-secondary mb-2">
                        <b-form-radio id="customRadiocolor4" checked> Secondary Radio </b-form-radio>
                    </div>
                    <div class="form-radio-warning mb-2">
                        <b-form-radio id="customRadiocolor5" checked> Warning Radio </b-form-radio>
                    </div>
                    <div class="form-radio-danger mb-2">
                        <b-form-radio id="customRadiocolor6" checked> Danger Radio </b-form-radio>
                    </div>
                    <div class="form-radio-dark">
                        <b-form-radio id="customRadiocolor7" checked> Dark Radio </b-form-radio>
                    </div>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col lg="6">
                <UICard title="Input Sizes">
                    <form>
                        <b-form-group label="Small" label-for="input-sm" class="mb-3">
                            <b-form-input id="input-sm" size="sm" placeholder=".input-sm" />
                        </b-form-group>

                        <b-form-group label="Normal" label-for="input-sm" class="mb-3">
                            <b-form-input id="input-sm" placeholder="Normal" />
                        </b-form-group>

                        <b-form-group label="Large" label-for="input-sm" class="mb-3">
                            <b-form-input id="input-sm" size="lg" placeholder=".input-lg" />
                        </b-form-group>

                        <div class="mb-2">
                            <label for="example-gridsize" class="form-label">Grid Sizes</label>
                            <b-row>
                                <b-col sm="4">
                                    <b-form-input id="example-gridsize" placeholder=".col-sm-4" />
                                </b-col>
                            </b-row>
                        </div>
                    </form>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Input Group">
                    <b-form>
                        <b-form-group label="Static" class="mb-3">
                            <b-input-group prepend="@">
                                <b-form-input placeholder="Username" />
                            </b-input-group>
                        </b-form-group>

                        <b-form-group label="Dropdowns" class="mb-3">
                            <b-input-group>
                                <template #prepend>
                                    <b-dropdown variant="primary" text="Dropdown">
                                        <b-dropdown-item>Action</b-dropdown-item>
                                        <b-dropdown-item>Another action</b-dropdown-item>
                                        <b-dropdown-item>Something else here</b-dropdown-item>
                                    </b-dropdown>
                                </template>
                                <b-form-input placeholder="" />
                            </b-input-group>
                        </b-form-group>

                        <b-form-group label="Buttons" label-for="exampleDataList" class="mb-3">
                            <b-input-group>
                                <b-form-input placeholder="Recipient's username" />
                                <template #append>
                                    <b-button variant="dark" type="button">Button</b-button>
                                </template>
                            </b-input-group>
                        </b-form-group>

                        <b-row class="g-2">
                            <b-col sm="6">
                                <b-form-file v-model="file" label="File input" />
                            </b-col>
                            <b-col sm="6">
                                <b-form-file v-model="file" label="Multiple files input" multiple />
                            </b-col>
                        </b-row>
                    </b-form>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Custom Switch">
                    <div class="hstack gap-1">
                        <!-- without label-->
                        <input type="checkbox" id="switch0" data-switch="none" />
                        <label for="switch0" data-on-label="" data-off-label=""></label>

                        <!-- Bool Switch-->
                        <input type="checkbox" id="switch1" checked data-switch="bool" />
                        <label for="switch1" data-on-label="On" data-off-label="Off"></label>

                        <!-- Primary Switch-->
                        <input type="checkbox" id="switch2" checked data-switch="primary" />
                        <label for="switch2" data-on-label="On" data-off-label="Off"></label>

                        <!-- Success Switch-->
                        <input type="checkbox" id="switch3" checked data-switch="success" />
                        <label for="switch3" data-on-label="Yes" data-off-label="No"></label>

                        <!-- Info Switch-->
                        <input type="checkbox" id="switch4" checked data-switch="info" />
                        <label for="switch4" data-on-label="On" data-off-label="Off"></label>

                        <!-- Warning Switch-->
                        <input type="checkbox" id="switch5" checked data-switch="warning" />
                        <label for="switch5" data-on-label="Yes" data-off-label="No"></label>

                        <!-- Danger Switch-->
                        <input type="checkbox" id="switch6" checked data-switch="danger" />
                        <label for="switch6" data-on-label="On" data-off-label="Off"></label>

                        <!-- Dark Switch-->
                        <input type="checkbox" id="switch7" checked data-switch="secondary" />
                        <label for="switch7" data-on-label="Yes" data-off-label="No"></label>

                        <!-- Disabled Switch-->
                        <input type="checkbox" id="switchdis" data-switch="primary" checked disabled />
                        <label for="switchdis" data-on-label="On" data-off-label="Off"></label>
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
import { ref } from 'vue';

const file = ref<null | File>(null);
</script>
