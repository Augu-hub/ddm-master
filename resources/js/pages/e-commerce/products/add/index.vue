<template>
    <VerticalLayout>
        <PageTitle title="Add Products" subtitle="eCommerce" />

        <b-row>
            <b-col lg="7">
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Product information</b-card-title>
                        <p class="text-muted mb-0">
                            Product Information encompasses all data within an organization pertaining to the products it manufactures, procures,
                            sells, or distributes.
                        </p>
                    </b-card-header>
                    <b-card-body>
                        <b-row>
                            <b-col lg="6">
                                <b-form-group label="Product Name" label-for="productName" class="mb-3">
                                    <b-form-input type="text" id="productName" placeholder="Enter Product Name" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="SKU Id Last Name" label-for="skuId" class="mb-3">
                                    <b-form-input type="number" id="skuId" placeholder="#19588" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="12">
                                <b-form-group label="Product Description" label-for="description" class="mb-3">
                                    <b-form-textarea id="description" :rows="7" placeholder="Short description about the product" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <b-form-group label="Product Code" label-for="productCode" class="mb-3">
                                    <b-form-input type="text" id="productCode" placeholder="PC198866" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="productCategory" label="Product Category" class="my-md-0 me-sm-3 my-1">
                                        <option>Electronics</option>
                                        <option>Mobile Accessories</option>
                                        <option>Games</option>
                                        <option>Sports</option>
                                        <option>Watches</option>
                                        <option>Bags</option>
                                        <option>Toys</option>
                                        <option>Cloth's</option>
                                        <option>Shoes</option>
                                        <option>Fashion</option>
                                        <option>Furniture</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="productType" label="Product Type" class="my-md-0 me-sm-3 my-1">
                                        <option>Simple</option>
                                        <option>Classy</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="productBrand" label="Product Brand" class="my-md-0 me-sm-3 my-1">
                                        <option>Sony</option>
                                        <option>Canon</option>
                                        <option>Snitch</option>
                                        <option>Titan</option>
                                        <option>JCB</option>
                                        <option>Wood</option>
                                        <option>Apple</option>
                                        <option>Nike</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="productStatus" label="Status" class="my-md-0 me-sm-3 my-1">
                                        <option>Draft</option>
                                        <option>Scheduled</option>
                                        <option>Published</option>
                                        <option>Entertainment</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>

                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="productVisibility" label="Visibility" class="my-md-0 me-sm-3 my-1">
                                        <option>Public</option>
                                        <option>Hidden</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>

                            <b-col lg="6">
                                <FlatPicker v-model="date" placeholder="Basic datepicker" id="basic-datepicker" label="Publish Date & Time" />
                            </b-col>

                            <b-col lg="6">
                                <b-form-group label="Price" label-for="productPrice" class="mb-3">
                                    <b-form-input type="number" id="productPrice" placeholder="$00.00" />
                                </b-form-group>
                            </b-col>

                            <b-col lg="6">
                                <b-form-group label="Discounts" label-for="productDiscounts" class="mb-3">
                                    <b-form-input type="number" id="productDiscounts" placeholder="0%" />
                                </b-form-group>
                            </b-col>

                            <b-col lg="6">
                                <div class="mb-3">
                                    <ChoicesSelect id="productTax" label="Tax" class="my-md-0 me-sm-3 my-1">
                                        <option>5%</option>
                                        <option>8%</option>
                                        <option>12%</option>
                                        <option>16%</option>
                                        <option>20%</option>
                                        <option>22%</option>
                                    </ChoicesSelect>
                                </div>
                            </b-col>
                            <b-col lg="4">
                                <h5>Product Size</h5>
                                <b-form-group label="Length" label-for="productLength" class="mb-3 pt-2">
                                    <b-form-input type="number" id="productLength" placeholder="0.00cm" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="4">
                                <b-form-group label="Breadth" label-for="productBreadth" class="my-4">
                                    <b-form-input type="number" id="productBreadth" placeholder="0.00cm" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="4">
                                <b-form-group label="Width" label-for="productWidth" class="my-4">
                                    <b-form-input type="number" id="productWidth" placeholder="0.00cm" />
                                </b-form-group>
                            </b-col>
                            <b-col lg="6">
                                <h5>Colors</h5>
                                <div
                                    class="d-flex align-items-center mt-2 flex-wrap gap-1"
                                    role="group"
                                    aria-label="Basic checkbox toggle button group"
                                >
                                    <input type="checkbox" class="btn-check" id="color-dark2" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-dark2"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-success border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-yellow2" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-yellow2"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-warning border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-white2" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-white2"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-primary border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-info" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-info"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-info border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-danger" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-danger"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-danger border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-black" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-black"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-dark border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-light" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-light"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-light border"></i
                                    ></label>

                                    <input type="checkbox" class="btn-check" id="color-purple" />
                                    <label
                                        class="btn avatar btn-icon rounded-circle d-flex justify-content-center align-items-center"
                                        for="color-purple"
                                    >
                                        <i class="ti ti-circle-filled fs-28 rounded-circle text-secondary border"></i
                                    ></label>
                                </div>
                            </b-col>
                        </b-row>
                    </b-card-body>
                </b-card>
            </b-col>

            <b-col lg="5">
                <b-card no-body>
                    <b-card-header class="border-bottom border-dashed">
                        <b-card-title tag="h4" class="mb-0">Product Gallery</b-card-title>
                        <p class="text-muted mb-0">You need at least 4 images. Pay attention to the quality of the pictures you add (important)</p>
                    </b-card-header>
                    <b-card-body>
                        <b-col cols="12">
                            <FileUpload v-model="uploadedFiles" />
                        </b-col>
                    </b-card-body>
                    <b-card-footer class="border-top border-dashed text-end">
                        <div class="d-flex justify-content-end gap-1">
                            <a href="#!" class="btn btn-primary">Create Product</a>
                            <a href="#!" class="btn btn-danger">Cancel</a>
                        </div>
                    </b-card-footer>
                </b-card>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>

<script setup lang="ts">
import ChoicesSelect from '@/components/ChoicesSelect.vue';
import FileUpload from '@/components/FileUpload.vue';
import FlatPicker from '@/components/FlatPicker.vue';
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { ref } from 'vue';

const date = ref();
const uploadedFiles = ref<File[]>([]);
</script>
