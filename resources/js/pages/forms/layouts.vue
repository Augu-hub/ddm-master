<template>
    <VerticalLayout>
        <PageTitle title="Form Layouts" subtitle="Forms" />
        <b-row>
            <b-col lg="6">
                <UICard title="Basic Example">
                    <b-form>
                        <b-form-group label="Email address" label-for="exampleInputEmail1" class="mb-3">
                            <b-form-input type="email" id="exampleInputEmail1" />
                            <small id="emailHelp" class="form-text text-muted">We'll never share your email with anyone else.</small>
                        </b-form-group>
                        <b-form-group label="Password" label-for="exampleInputEmail1" class="mb-3">
                            <b-form-input type="password" id="exampleInputPassword1" placeholder="Password" />
                        </b-form-group>
                        <b-form-checkbox class="mb-3">Check me out !</b-form-checkbox>
                        <b-button type="submit" variant="primary">Submit</b-button>
                    </b-form>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Horizontal form">
                    <b-form class="form-horizontal">
                        <b-form-group label-cols-lg="3" label="Email" label-for="inputEmail3" class="mb-3">
                            <b-form-input type="email" id="inputEmail3" placeholder="Email" />
                        </b-form-group>
                        <b-form-group label-cols-lg="3" label="Password" label-for="inputPassword3" class="mb-3">
                            <b-form-input type="password" id="inputPassword3" placeholder="Password" />
                        </b-form-group>
                        <b-form-group label-cols-lg="3" label="Re Password" label-for="inputPassword3" class="mb-3">
                            <b-form-input type="password" id="inputPassword3" placeholder="Retype Password" />
                        </b-form-group>
                        <b-row class="justify-content-end mb-3">
                            <b-col cols="9">
                                <b-form-checkbox class="mb-3">Check me out !</b-form-checkbox>
                            </b-col>
                        </b-row>
                        <b-row class="justify-content-end">
                            <b-col cols="9">
                                <b-button type="submit" variant="info">Sign in</b-button>
                            </b-col>
                        </b-row>
                    </b-form>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col md="12">
                <UICard title="Inline Form">
                    <b-form class="row row-cols-lg-auto g-3 align-items-center">
                        <b-col cols="12">
                            <b-form-group label-for="staticEmail2" label-class="visually-hidden">
                                <b-form-input
                                    type="email"
                                    id="staticEmail2"
                                    class="form-control-plaintext"
                                    readonly
                                    model-value="email@example.com"
                                />
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-form-group label-for="inputPassword2" label-class="visually-hidden">
                                <b-form-input type="password" id="inputPassword2" placeholder="Password" />
                            </b-form-group>
                        </b-col>
                        <b-col cols="12">
                            <b-button type="submit" variant="primary">Confirm identity</b-button>
                        </b-col>
                    </b-form>

                    <h6 class="fs-13 mt-3">Auto-sizing</h6>
                    <b-form>
                        <b-row class="gy-2 gx-2 align-items-center">
                            <div class="col-auto">
                                <b-form-group label-for="inlineFormInput" label-class="visually-hidden">
                                    <b-form-input type="text" id="inlineFormInput" class="mb-2" placeholder="Jane Doe" />
                                </b-form-group>
                            </div>
                            <div class="col-auto">
                                <b-form-group label-for="inlineFormInput" label-class="visually-hidden">
                                    <b-input-group prepend="@" class="mb-2">
                                        <b-form-input type="text" id="inlineFormInputGroup" placeholder="Username" />
                                    </b-input-group>
                                </b-form-group>
                            </div>
                            <div class="col-auto">
                                <b-form-checkbox class="mb-2">Remember me</b-form-checkbox>
                            </div>
                            <div class="col-auto">
                                <b-button type="submit" variant="primary" class="mb-2">Submit</b-button>
                            </div>
                        </b-row>
                    </b-form>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Horizontal form label sizing">
                    <b-form>
                        <b-form-group label-cols-sm="2" content-cols-sm label="Email" label-for="colFormLabelSm" class="row mb-2">
                            <b-form-input type="email" size="sm" id="colFormLabelSm" placeholder="col-form-label-sm" />
                        </b-form-group>
                        <b-form-group label-cols-sm="2" content-cols-sm label="Email" label-for="colFormLabel" class="row mb-2">
                            <b-form-input type="email" id="colFormLabel" placeholder="col-form-label" />
                        </b-form-group>
                        <b-form-group label-cols-sm="2" content-cols-sm label="Email" label-for="colFormLabelLg" class="row">
                            <b-form-input type="email" size="lg" id="colFormLabelLg" placeholder="col-form-label-lg" />
                        </b-form-group>
                    </b-form>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col cols="12">
                <UICard title="Form Row">
                    <b-form>
                        <b-row class="g-2">
                            <b-form-group label="Email" label-for="inputEmail4" class="col-md-6 mb-3">
                                <b-form-input type="email" id="inputEmail4" placeholder="Email" />
                            </b-form-group>
                            <b-form-group label="Password" label-for="inputPassword4" class="col-md-6 mb-3">
                                <b-form-input type="password" id="inputPassword4" placeholder="Password" />
                            </b-form-group>
                        </b-row>

                        <div class="mb-3">
                            <b-form-group label="Address" label-for="inputAddress">
                                <b-form-input type="text" id="inputAddress" placeholder="1234 Main St" />
                            </b-form-group>
                        </div>

                        <div class="mb-3">
                            <b-form-group label="Address 2" label-for="inputAddress2">
                                <b-form-input type="text" id="inputAddress2" placeholder="Apartment, studio, or floor" />
                            </b-form-group>
                        </div>

                        <b-row class="g-2">
                            <b-form-group label="City" label-for="inputCity" class="col-md-6 mb-3">
                                <b-form-input type="text" id="inputCity" />
                            </b-form-group>
                            <b-form-group label="State" label-for="inputState" class="col-md-4 mb-3">
                                <b-form-select :model-value="1">
                                    <option value="1">Choose</option>
                                    <option value="2">Option 1</option>
                                    <option value="3">Option 2</option>
                                    <option value="4">Option 3</option>
                                </b-form-select>
                            </b-form-group>
                            <b-form-group label="Zip" label-for="inputZip" class="col-md-2 mb-3">
                                <b-form-input type="text" id="inputZip" />
                            </b-form-group>
                        </b-row>

                        <div class="mb-2">
                            <b-form-checkbox class="fs-15">Check this custom checkbox</b-form-checkbox>
                        </div>

                        <b-button type="submit" variant="primary">Sign in</b-button>
                    </b-form>
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
