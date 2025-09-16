<template>
    <b-card no-body>
        <b-card-header class="border-bottom border-dashed">
            <b-card-title tag="h4" class="mb-0">Popular Brands</b-card-title>
        </b-card-header>
        <b-card-body>
            <div class="categories-list d-flex flex-column mb-2 gap-2">
                <b-form-checkbox v-for="option in popularBrands" :key="option.value" :value="option.value">
                    {{ option.text }}
                </b-form-checkbox>
            </div>
            <a href="#!" class="text-primary fw-medium">More <i class="ti ti-arrow-right align-middle"></i></a>
        </b-card-body>
    </b-card>
    <b-card no-body>
        <b-card-header class="border-bottom border-dashed">
            <b-card-title tag="h4" class="mb-0">Popular Categories</b-card-title>
        </b-card-header>
        <b-card-body>
            <div class="categories-list d-flex flex-column mb-2 gap-2">
                <div class="d-flex justify-content-between">
                    <b-form-checkbox>All Categories</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">5352</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Furniture</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">624</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Headphones</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">351</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Eye Ware & Sunglass</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">98</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Foot Ware</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">452</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Fashion Men , Women & Kid's</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">2120</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Electronics Items</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">667</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Watches</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">80</b-badge>
                    </div>
                </div>

                <div class="d-flex justify-content-between">
                    <b-form-checkbox>Beauty & Health</b-form-checkbox>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">960</b-badge>
                    </div>
                </div>
            </div>
            <a href="#!" class="text-primary fw-medium">More <i class="ti ti-arrow-right align-middle"></i></a>
        </b-card-body>
    </b-card>
    <b-card no-body>
        <b-card-header class="border-bottom border-dashed">
            <b-card-title tag="h4" class="mb-0">Custom Price</b-card-title>
        </b-card-header>
        <b-card-body>
            <div class="d-flex flex-column mb-3 gap-2">
                <b-form-checkbox v-for="option in customPrice" :key="option.value" :value="option.value">
                    {{ option.text }}
                </b-form-checkbox>
            </div>
            <VueSlider v-model="priceRange" :min="38" :max="1500" :dot-style="sliderStyle" :process-style="sliderStyle" tooltip="none" />
            <div class="formCost d-flex align-items-center mt-3 gap-2">
                <b-form-input size="sm" class="text-center" type="text" id="minCost" v-model="priceRange[0]" />
                <span class="fw-semibold text-muted">to</span>
                <b-form-input size="sm" class="text-center" type="text" id="maxCost" v-model="priceRange[1]" />
            </div>
        </b-card-body>
    </b-card>
    <b-card no-body>
        <b-card-header class="border-bottom border-dashed">
            <b-card-title tag="h4" class="mb-0">Rating</b-card-title>
        </b-card-header>
        <b-card-body>
            <div class="categories-list d-flex flex-column gap-2">
                <div class="d-flex justify-content-between">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="five-star" />
                        <label class="form-check-label" for="five-star"
                            ><span class="d-inline-flex align-items-center flex-grow-1">
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ms-1">5 Star Rating </span>
                            </span></label
                        >
                    </div>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">452</b-badge>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="four-star" />
                        <label class="form-check-label" for="four-star"
                            ><span class="d-inline-flex align-items-center flex-grow-1">
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ms-1">4 Star Rating</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">622</b-badge>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="three-star" />
                        <label class="form-check-label" for="three-star"
                            ><span class="d-inline-flex align-items-center flex-grow-1">
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ms-1">3 Star Rating</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">389</b-badge>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="two-star" />
                        <label class="form-check-label" for="two-star"
                            ><span class="d-inline-flex align-items-center flex-grow-1">
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ms-1">2 Star Rating</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">215</b-badge>
                    </div>
                </div>
                <div class="d-flex justify-content-between">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="one-star" />
                        <label class="form-check-label" for="one-star"
                            ><span class="d-inline-flex align-items-center flex-grow-1">
                                <span class="ti ti-star-filled text-warning"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ti ti-star text-muted"></span>
                                <span class="ms-1">1 Star Rating</span>
                            </span>
                        </label>
                    </div>
                    <div>
                        <b-badge :variant="null" class="bg-primary-subtle text-primary">546</b-badge>
                    </div>
                </div>
            </div>
        </b-card-body>
    </b-card>

    <b-card no-body>
        <div class="card-footer">
            <a href="#!" class="btn btn-primary w-100">Apply Filter</a>
        </div>
    </b-card>
</template>

<script setup lang="ts">
import VueSlider from 'vue-3-slider-component';

import { ref } from 'vue';

const priceRange = ref([200, 1299]);

const popularBrands = [
    { text: 'Samsung', value: 'Samsung' },
    { text: 'Sony', value: 'Sony' },
    { text: 'Apple', value: 'Apple' },
    { text: 'H & M', value: 'H & M' },
    { text: 'Black Berry', value: 'Black Berry' },
    { text: 'Skullcandy', value: 'Skullcandy' },
    { text: 'Zara', value: 'Zara' },
    { text: 'Noise', value: 'Noise' },
    { text: 'Nike', value: 'Nike' },
    { text: 'Adidas', value: 'Adidas' },
];

const customPrice = [
    { text: 'All Price', value: 'all-price' },
    { text: 'Below $200 (221)', value: '<200' },
    { text: '$200 - $500 (172)', value: '200-500' },
    { text: '$500 - $800 (331)', value: '500-800' },
    { text: '$800 - $1000 (455)', value: '800-1000' },
    { text: '$1000 - $1200 (1290)', value: '1000-1200' },
];

const sliderStyle = {
    backgroundColor: '#465dff',
};
</script>
