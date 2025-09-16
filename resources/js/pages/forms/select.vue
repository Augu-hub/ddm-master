<template>
    <VerticalLayout>
        <PageTitle title="Form Select" subtitle="Forms" />
        <b-row>
            <b-col lg="12">
                <UICard title="Choices">
                    <div>
                        <h5 class="fs-14 mb-2">Single select input Example</h5>
                        <b-row>
                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect
                                        id="choices-single-default"
                                        label="Default"
                                        v-model="basicSelected"
                                        :options="options1"
                                        class="form-control"
                                    />
                                </div>
                            </b-col>
                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="choices-single-groups" label="Option Groups" class="form-control" v-model="optionGrpSelected">
                                        <option value="">Choose a city</option>
                                        <optgroup label="UK">
                                            <option value="London">London</option>
                                            <option value="Manchester">Manchester</option>
                                            <option value="Liverpool">Liverpool</option>
                                        </optgroup>
                                        <optgroup label="FR">
                                            <option value="Paris">Paris</option>
                                            <option value="Lyon">Lyon</option>
                                            <option value="Marseille">Marseille</option>
                                        </optgroup>
                                        <optgroup label="DE" disabled>
                                            <option value="Hamburg">Hamburg</option>
                                            <option value="Munich">Munich</option>
                                            <option value="Berlin">Berlin</option>
                                        </optgroup>
                                        <optgroup label="US">
                                            <option value="New York">New York</option>
                                            <option value="Washington" disabled>Washington</option>
                                            <option value="Michigan">Michigan</option>
                                        </optgroup>
                                        <optgroup label="SP">
                                            <option value="Madrid">Madrid</option>
                                            <option value="Barcelona">Barcelona</option>
                                            <option value="Malaga">Malaga</option>
                                        </optgroup>
                                        <optgroup label="CA">
                                            <option value="Montreal">Montreal</option>
                                            <option value="Toronto">Toronto</option>
                                            <option value="Vancouver">Vancouver</option>
                                        </optgroup>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect
                                        id="choices-single-no-search"
                                        label="Options added via config with no search"
                                        v-model="noSearchSelected"
                                        :options="options2"
                                        :choice-options="{ searchEnabled: false }"
                                        class="form-control"
                                    />
                                </div>
                            </b-col>
                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect
                                        id="choices-single-no-sorting"
                                        label="Options added via config with no sorting"
                                        v-model="noSortingSelected"
                                        :options="options3"
                                        :choice-options="{ shouldSort: false, shouldSortItems: false }"
                                        class="form-control"
                                    />
                                </div>
                            </b-col>
                        </b-row>
                    </div>

                    <div class="mt-4">
                        <h5 class="fs-14 mb-3">Multiple select input</h5>
                        <b-row>
                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect
                                        id="choices-multiple-default"
                                        label="Default"
                                        v-model="multipleSelected"
                                        :options="options4"
                                        multiple
                                    />
                                </div>
                            </b-col>

                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect
                                        id="choices-multiple-remove-button"
                                        label="With remove button"
                                        v-model="multipleWithRemoveSelected"
                                        :options="options4"
                                        :choice-options="{ removeItemButton: true }"
                                        multiple
                                    />
                                </div>
                            </b-col>

                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesSelect
                                        id="choices-multiple-groups"
                                        label="Option groups"
                                        v-model="multipleWithOptionGrpSelected"
                                        multiple
                                    >
                                        <option value="1">Choose a city</option>
                                        <optgroup label="UK">
                                            <option value="London">London</option>
                                            <option value="Manchester">Manchester</option>
                                            <option value="Liverpool">Liverpool</option>
                                        </optgroup>
                                        <optgroup label="FR">
                                            <option value="Paris">Paris</option>
                                            <option value="Lyon">Lyon</option>
                                            <option value="Marseille">Marseille</option>
                                        </optgroup>
                                        <optgroup label="DE" disabled>
                                            <option value="Hamburg">Hamburg</option>
                                            <option value="Munich">Munich</option>
                                            <option value="Berlin">Berlin</option>
                                        </optgroup>
                                        <optgroup label="US">
                                            <option value="New York">New York</option>
                                            <option value="Washington" disabled>Washington</option>
                                            <option value="Michigan">Michigan</option>
                                        </optgroup>
                                        <optgroup label="SP">
                                            <option value="Madrid">Madrid</option>
                                            <option value="Barcelona">Barcelona</option>
                                            <option value="Malaga">Malaga</option>
                                        </optgroup>
                                        <optgroup label="CA">
                                            <option value="Montreal">Montreal</option>
                                            <option value="Toronto">Toronto</option>
                                            <option value="Vancouver">Vancouver</option>
                                        </optgroup>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                        </b-row>
                    </div>

                    <div class="mt-4">
                        <h5 class="fs-14 mb-3">Text inputs</h5>
                        <b-row>
                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesInput
                                        id="choices-text-remove-button"
                                        label="Set limit values with remove button"
                                        :choice-options="{ removeItemButton: true, maxItemCount: 3 }"
                                    />
                                </div>
                            </b-col>

                            <b-col lg="4" md="6">
                                <div class="mb-3">
                                    <ChoicesInput
                                        id="choices-text-unique-values"
                                        label="Unique values only, no pasting"
                                        value="Project-A, Project-B"
                                        :choice-options="{ duplicateItemsAllowed: false, paste: false }"
                                    />
                                </div>
                            </b-col>
                        </b-row>
                    </div>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import ChoicesInput from '@/components/ChoicesInput.vue';
import ChoicesSelect from '@/components/ChoicesSelect.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

const basicSelected = ref('');
const optionGrpSelected = ref('');
const noSearchSelected = ref('Zero');
const noSortingSelected = ref('Madrid');
const multipleSelected = ref('choice-1');
const multipleWithRemoveSelected = ref('choice-3');
const multipleWithOptionGrpSelected = ref('');

const options1 = [
    { value: '', text: 'This is a placeholder' },
    { value: 'choice-1', text: 'Choice 1' },
    { value: 'choice-2', text: 'Choice 2' },
    { value: 'choice-3', text: 'Choice 3' },
    { value: 'choice-4', text: 'Choice 4' },
];

const options2 = [
    { value: 'Zero', text: 'Zero' },
    { value: 'One', text: 'One' },
    { value: 'Two', text: 'Two' },
    { value: 'Three', text: 'Three' },
    { value: 'Four', text: 'Four' },
    { value: 'Five', text: 'Five' },
    { value: 'Six', text: 'Six' },
];

const options3 = [
    { value: 'Madrid', text: 'Madrid' },
    { value: 'Toronto', text: 'Toronto' },
    { value: 'Vancouver', text: 'Vancouver' },
    { value: 'London', text: 'London' },
    { value: 'Manchester', text: 'Manchester' },
    { value: 'Liverpool', text: 'Liverpool' },
    { value: 'Paris', text: 'Paris' },
    { value: 'Malaga', text: 'Malaga' },
    { value: 'Washington', text: 'Washington' },
    { value: 'Lyon', text: 'Lyon' },
    { value: 'Marseille', text: 'Marseille' },
    { value: 'Hamburg', text: 'Hamburg' },
    { value: 'Munich', text: 'Munich' },
    { value: 'Madrid', text: 'Madrid' },
    { value: 'Barcelona', text: 'Barcelona' },
    { value: 'Berlin', text: 'Berlin' },
    { value: 'Montreal', text: 'Montreal' },
    { value: 'New York', text: 'New York' },
    { value: 'Michigan', text: 'Michigan' },
];

const options4 = [
    { value: 'choice-1', text: 'Choice 1' },
    { value: 'choice-2', text: 'Choice 2' },
    { value: 'choice-3', text: 'Choice 3' },
    { value: 'choice-4', text: 'Choice 4' },
];
</script>
