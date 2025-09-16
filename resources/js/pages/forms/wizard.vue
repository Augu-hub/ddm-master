<template>
    <VerticalLayout>
        <PageTitle title="Form Wizard" subtitle="Forms" />
        <b-row>
            <b-col lg="6">
                <UICard title="A Basic Wizard">
                    <form>
                        <div id="basicwizard">
                            <ul class="nav nav-pills nav-justified form-wizard-header mb-4">
                                <li class="nav-item">
                                    <a href="#basictab1" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-person-circle fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Account</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#basictab2" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-emoji-smile fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#basictab3" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-check2-circle fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Finish</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content b-0 mb-0">
                                <div class="tab-pane" id="basictab1">
                                    <b-row>
                                        <b-col cols="12">
                                            <b-row class="mb-3">
                                                <b-form-group label="User name" label-cols-md="3">
                                                    <b-form-input type="text" id="userName" name="userName" model-value="johne" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Password" label-cols-md="3">
                                                    <b-form-input type="password" id="password" name="password" model-value="123456789" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Re Password" label-cols-md="3">
                                                    <b-form-input type="password" id="confirm" name="confirm" model-value="123456789" />
                                                </b-form-group>
                                            </b-row>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div class="tab-pane" id="basictab2">
                                    <b-row>
                                        <b-col cols="12">
                                            <b-row class="mb-3">
                                                <b-form-group label="First name" label-cols-md="3">
                                                    <b-form-input type="text" id="name" name="name" model-value="Francis" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Last name" label-cols-md="3">
                                                    <b-form-input type="text" id="surname" name="surname" model-value="Brinkman" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Email" label-cols-md="3">
                                                    <b-form-input type="email" id="email" name="email" model-value="cory1979@hotmail.com" />
                                                </b-form-group>
                                            </b-row>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div class="tab-pane" id="basictab3">
                                    <b-row>
                                        <b-col cols="12">
                                            <div class="text-center">
                                                <h2 class="mt-0"><i class="bi bi-check2-all"></i></h2>
                                                <h3 class="mt-0">Thank you !</h3>

                                                <p class="w-75 mx-auto mb-2">
                                                    Quisque nec turpis at urna dictum luctus. Suspendisse convallis dignissim eros at volutpat. In
                                                    egestas mattis dui. Aliquam mattis dictum aliquet.
                                                </p>

                                                <div class="mb-3">
                                                    <div class="form-check d-inline-block">
                                                        <b-form-checkbox id="customCheck1" class="fs-15"
                                                            >I agree with the Terms and Conditions</b-form-checkbox
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div class="d-flex wizard justify-content-between mt-3 flex-wrap gap-2">
                                    <div class="first">
                                        <a href="javascript:void(0);" class="btn btn-primary"> First </a>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="previous">
                                            <a href="javascript:void(0);" class="btn btn-primary">
                                                <i class="bx bx-left-arrow-alt me-2"></i>Back To Previous
                                            </a>
                                        </div>
                                        <div class="next">
                                            <a href="javascript:void(0);" class="btn btn-primary mt-md-0 mt-3">
                                                Next Step<i class="bx bx-right-arrow-alt ms-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="last">
                                        <a href="javascript:void(0);" class="btn btn-primary mt-md-0 mt-3"> Finish </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Wizard With Progress Bar">
                    <form>
                        <div id="progressbarwizard">
                            <ul class="nav nav-pills nav-justified form-wizard-header mb-3">
                                <li class="nav-item">
                                    <a href="#account-2" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-person-circle fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Account</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#profile-tab-2" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-emoji-smile fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="#finish-2" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-check2-circle fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Finish</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content b-0 mb-0">
                                <div id="bar" class="progress mb-3" style="height: 7px">
                                    <div class="bar progress-bar progress-bar-striped progress-bar-animated bg-success"></div>
                                </div>

                                <div class="tab-pane" id="account-2">
                                    <b-row>
                                        <b-col cols="12">
                                            <b-row class="mb-3">
                                                <b-form-group label="User name" label-cols-md="3">
                                                    <b-form-input type="text" id="userName1" name="userName1" model-value="johne" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Password" label-cols-md="3">
                                                    <b-form-input type="password1" id="password1" name="password1" model-value="123456789" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Re Password" label-cols-md="3">
                                                    <b-form-input type="confirm1" id="confirm1" name="confirm1" model-value="123456789" />
                                                </b-form-group>
                                            </b-row>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div class="tab-pane" id="profile-tab-2">
                                    <b-row>
                                        <b-col cols="12">
                                            <b-row class="mb-3">
                                                <b-form-group label="First name" label-cols-md="3">
                                                    <b-form-input type="text" id="name1" name="name1" model-value="Francis" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Last name" label-cols-md="3">
                                                    <b-form-input type="text" id="surname1" name="surname1" model-value="Brinkman" />
                                                </b-form-group>
                                            </b-row>
                                            <b-row class="mb-3">
                                                <b-form-group label="Email" label-cols-md="3">
                                                    <b-form-input type="email" id="email" name="email" model-value="cory1979@hotmail.com" />
                                                </b-form-group>
                                            </b-row>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div class="tab-pane" id="finish-2">
                                    <b-row>
                                        <b-col cols="12">
                                            <div class="text-center">
                                                <h2 class="mt-0"><i class="bi bi-check2-all"></i></h2>
                                                <h3 class="mt-0">Thank you !</h3>

                                                <p class="w-75 mx-auto mb-2">
                                                    Quisque nec turpis at urna dictum luctus. Suspendisse convallis dignissim eros at volutpat. In
                                                    egestas mattis dui. Aliquam mattis dictum aliquet.
                                                </p>

                                                <div class="mb-3">
                                                    <div class="form-check d-inline-block">
                                                        <b-form-checkbox id="customCheck3" class="fs-15"
                                                            >I agree with the Terms and Conditions</b-form-checkbox
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </b-col>
                                    </b-row>
                                </div>

                                <div class="d-flex wizard justify-content-between mt-3 flex-wrap gap-2">
                                    <div class="first">
                                        <a href="javascript:void(0);" class="btn btn-primary"> First </a>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="previous">
                                            <a href="javascript:void(0);" class="btn btn-primary">
                                                <i class="bx bx-left-arrow-alt me-2"></i>Back To Previous
                                            </a>
                                        </div>
                                        <div class="next">
                                            <a href="javascript:void(0);" class="btn btn-primary mt-md-0 mt-3">
                                                Next Step<i class="bx bx-right-arrow-alt ms-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="last">
                                        <a href="javascript:void(0);" class="btn btn-primary mt-md-0 mt-3"> Finish </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </UICard>
            </b-col>

            <b-col lg="6">
                <UICard title="Wizard With Form Validation">
                    <form>
                        <div id="validation-wizard">
                            <ul class="nav nav-pills nav-justified form-wizard-header mb-3">
                                <li class="nav-item" data-target-form="#accountForm">
                                    <a href="#first" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-person-circle fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Account</span>
                                    </a>
                                </li>
                                <li class="nav-item" data-target-form="#profileForm">
                                    <a href="#second" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-emoji-smile fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Profile</span>
                                    </a>
                                </li>
                                <li class="nav-item" data-target-form="#otherForm">
                                    <a href="#third" data-bs-toggle="tab" data-toggle="tab" class="nav-link rounded-0 py-2">
                                        <i class="bi bi-check2-circle fs-18 me-1 align-middle"></i>
                                        <span class="d-none d-sm-inline">Finish</span>
                                    </a>
                                </li>
                            </ul>

                            <div class="tab-content">
                                <div class="tab-pane" id="first">
                                    <form id="accountForm" method="post" action="#" class="form-horizontal">
                                        <b-row>
                                            <b-col cols="12">
                                                <b-row class="mb-3">
                                                    <b-form-group label="User name" label-cols-md="3">
                                                        <b-form-input type="text" id="userName3" name="userName3" required />
                                                    </b-form-group>
                                                </b-row>
                                                <b-row class="mb-3">
                                                    <b-form-group label="Password" label-cols-md="3">
                                                        <b-form-input type="password3" id="password3" name="password3" required />
                                                    </b-form-group>
                                                </b-row>
                                                <b-row class="mb-3">
                                                    <b-form-group label="Re Password" label-cols-md="3">
                                                        <b-form-input type="password" id="confirm3" name="confirm3" required />
                                                    </b-form-group>
                                                </b-row>
                                            </b-col>
                                        </b-row>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="second">
                                    <form id="profileForm" method="post" action="#" class="form-horizontal">
                                        <b-row>
                                            <b-col cols="12">
                                                <b-row class="mb-3">
                                                    <b-form-group label="First name" label-cols-md="3">
                                                        <b-form-input type="text" id="name" name="name" required />
                                                    </b-form-group>
                                                </b-row>
                                                <b-row class="mb-3">
                                                    <b-form-group label="Last name" label-cols-md="3">
                                                        <b-form-input type="text" id="surname3" name="surname3" required />
                                                    </b-form-group>
                                                </b-row>
                                                <b-row class="mb-3">
                                                    <b-form-group label="Email" label-cols-md="3">
                                                        <b-form-input type="email" id="email" name="email" required />
                                                    </b-form-group>
                                                </b-row>
                                            </b-col>
                                        </b-row>
                                    </form>
                                </div>

                                <div class="tab-pane fade" id="third">
                                    <form id="otherForm" method="post" action="#" class="form-horizontal">
                                        <b-row>
                                            <b-col cols="12">
                                                <div class="text-center">
                                                    <h2 class="mt-0">
                                                        <i class="bi bi-check2-all"></i>
                                                    </h2>
                                                    <h3 class="mt-0">Thank you !</h3>

                                                    <p class="w-75 mx-auto mb-2">
                                                        Quisque nec turpis at urna dictum luctus. Suspendisse convallis dignissim eros at volutpat. In
                                                        egestas mattis dui. Aliquam mattis dictum aliquet.
                                                    </p>

                                                    <div class="mb-3">
                                                        <div class="form-check d-inline-block">
                                                            <b-form-checkbox id="customCheck4" class="fs-15" required
                                                                >I agree with the Terms and Conditions</b-form-checkbox
                                                            >
                                                        </div>
                                                    </div>
                                                </div>
                                            </b-col>
                                        </b-row>
                                    </form>
                                </div>

                                <div class="d-flex wizard justify-content-between mt-3 flex-wrap gap-2">
                                    <div class="first">
                                        <a href="javascript:void(0);" class="btn btn-primary"> First </a>
                                    </div>
                                    <div class="d-flex flex-wrap gap-2">
                                        <div class="previous">
                                            <a href="javascript:void(0);" class="btn btn-primary">
                                                <i class="bx bx-left-arrow-alt me-2"></i>Back To Previous
                                            </a>
                                        </div>
                                        <div class="next">
                                            <a href="javascript:void(0);" class="btn btn-primary mt-md-0 mt-3">
                                                Next Step<i class="bx bx-right-arrow-alt ms-2"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <div class="last">
                                        <a href="javascript:void(0);" class="btn btn-primary mt-md-0 mt-3"> Finish </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import Wizard from '@/helpers/wizard';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { onMounted } from 'vue';

onMounted(() => {
    new Wizard('#basicwizard');

    new Wizard('#progressbarwizard', {
        progress: true,
    });

    new Wizard('#validation-wizard', {
        validate: true,
    });
});
</script>
