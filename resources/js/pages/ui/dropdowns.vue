<template>
    <VerticalLayout>
        <PageTitle title="Dropdowns" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Single button dropdowns">
                    <b-row>
                        <div class="col-auto">
                            <b-dropdown variant="light" text="Dropdown button">
                                <b-dropdown-item>Action</b-dropdown-item>
                                <b-dropdown-item>Another action</b-dropdown-item>
                                <b-dropdown-item>Something else here</b-dropdown-item>
                            </b-dropdown>
                        </div>

                        <div class="col-auto">
                            <b-dropdown toggle-class="btn-secondary p-0 m-0" no-caret>
                                <template #button-content>
                                    <a href="#" class="btn btn-secondary dropdown-toggle"> Dropdown link </a>
                                </template>
                                <b-dropdown-item>Action</b-dropdown-item>
                                <b-dropdown-item>Another action</b-dropdown-item>
                                <b-dropdown-item>Something else here</b-dropdown-item>
                            </b-dropdown>
                        </div>
                    </b-row>
                </UICard>

                <UICard title="Custom Dropdown Arrow">
                    <b-row>
                        <div class="col-auto">
                            <b-dropdown variant="primary" text="Without Arrow" no-caret>
                                <b-dropdown-item>Action</b-dropdown-item>
                                <b-dropdown-item>Another action</b-dropdown-item>
                                <b-dropdown-item>Something else here</b-dropdown-item>
                            </b-dropdown>
                        </div>
                        <div class="col-auto">
                            <b-dropdown variant="outline-info" no-caret>
                                <template #button-content> Tabler Icon <i class="ti ti-chevron-down ms-1 align-middle"></i> </template>
                                <b-dropdown-item>Action</b-dropdown-item>
                                <b-dropdown-item>Another action</b-dropdown-item>
                                <b-dropdown-item>Something else here</b-dropdown-item>
                            </b-dropdown>
                        </div>
                        <div class="col-auto">
                            <b-dropdown toggle-class="bg-gradient" no-caret>
                                <template #button-content>
                                    Lucid Icon
                                    <SquareChevronDownIcon class="avatar-xxs ms-2" />
                                </template>
                                <b-dropdown-item>Action</b-dropdown-item>
                                <b-dropdown-item>Another action</b-dropdown-item>
                                <b-dropdown-item>Something else here</b-dropdown-item>
                            </b-dropdown>
                        </div>
                        <div class="col-auto">
                            <b-dropdown :variant="null" toggle-class="btn-soft-success" no-caret>
                                <template #button-content>
                                    Solar Icon
                                    <Icon icon="solar:album-bold-duotone" class="fs-20 ms-2" />
                                </template>
                                <b-dropdown-item>Action</b-dropdown-item>
                                <b-dropdown-item>Another action</b-dropdown-item>
                                <b-dropdown-item>Something else here</b-dropdown-item>
                            </b-dropdown>
                        </div>
                    </b-row>
                </UICard>

                <UICard title="Variant">
                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="primary" text="Primary">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="light" text="Secondary">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="success" text="Success">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="info" text="Info">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="warning" text="Warning">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2">
                        <b-dropdown variant="danger" text="Danger">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Dropup variation">
                    <div class="btn-group dropup me-1">
                        <b-dropdown variant="light" dropup text="Dropup">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group dropup">
                        <b-dropdown variant="light" split dropup text="Split dropup">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Dropend variation">
                    <div class="btn-group dropend mb-2 me-1">
                        <b-dropdown variant="primary" dropend text="Dropend">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group dropend mb-2">
                        <b-dropdown variant="primary" split dropend text="Split Dropend">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Disabled Item">
                    <div class="btn-group">
                        <b-dropdown variant="primary" text="Disabled">
                            <b-dropdown-item>Regular link</b-dropdown-item>
                            <b-dropdown-item disabled>Disabled link</b-dropdown-item>
                            <b-dropdown-item>Another link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Dark dropdowns">
                    <b-dropdown text="Dropdown button" data-bs-theme="dark">
                        <b-dropdown-item>Action</b-dropdown-item>
                        <b-dropdown-item>Another action</b-dropdown-item>
                        <b-dropdown-item>Something else here</b-dropdown-item>
                        <b-dropdown-divider />
                        <b-dropdown-item>Separated link</b-dropdown-item>
                    </b-dropdown>
                </UICard>

                <UICard title="Dropdown options">
                    <div class="d-flex">
                        <b-dropdown class="me-1" offset="25" text="Offset">
                            <b-dropdown-item href="#">Action</b-dropdown-item>
                            <b-dropdown-item href="#">Another action</b-dropdown-item>
                            <b-dropdown-item href="#">Something else here</b-dropdown-item>
                        </b-dropdown>

                        <b-dropdown variant="secondary" split text="Reference">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Text">
                    <b-dropdown variant="primary" menu-class="p-0 m-0" text="Text Dropdown">
                        <div class="text-muted p-3" style="max-width: 200px">
                            <p>Some example text that's free-flowing within the dropdown menu.</p>
                            <p class="mb-0">And this is more example text.</p>
                        </div>
                    </b-dropdown>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Menu alignment">
                    <b-dropdown variant="light" end text="Right-aligned menu">
                        <b-dropdown-item href="#">Action</b-dropdown-item>
                        <b-dropdown-item href="#">Another action</b-dropdown-item>
                        <b-dropdown-item href="#">Something else here</b-dropdown-item>
                    </b-dropdown>
                </UICard>

                <UICard title="Split button dropdowns">
                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="primary" split text="Primary">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="light" split text="Secondary">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown :variant="null" toggle-class="btn-soft-success" split-class="btn-soft-success" split text="Success">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="info" split text="Info">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group mb-2 me-1">
                        <b-dropdown variant="warning" split text="Warning">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group">
                        <b-dropdown variant="danger" split text="Danger">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Sizing">
                    <!-- Large button groups (default and split) -->
                    <div class="btn-group me-1">
                        <b-dropdown variant="light" size="lg" text="Large button">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group me-1">
                        <b-dropdown variant="light" size="lg" split text="Large button">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <div class="btn-group me-1">
                        <b-dropdown variant="light" size="sm" text="Small button">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                    <div class="btn-group">
                        <b-dropdown variant="light" size="sm" split text="Small button">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Dropstart variation">
                    <!-- Default dropstart button -->
                    <div class="btn-group dropstart me-1">
                        <b-dropdown dropstart text="Dropup">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                            <b-dropdown-item>Something else here</b-dropdown-item>
                            <b-dropdown-divider />
                            <b-dropdown-item>Separated link</b-dropdown-item>
                        </b-dropdown>
                    </div>

                    <!-- Split dropstart button -->
                    <DropDown class="btn-group">
                        <div class="btn-group dropstart" role="group">
                            <button
                                type="button"
                                class="btn btn-secondary dropdown-toggle dropdown-split dropdown-toggle-split"
                                data-bs-toggle="dropdown"
                                aria-haspopup="true"
                                aria-expanded="false"
                            >
                                <span class="visually-hidden">Toggle Dropstart</span>
                            </button>
                            <div class="dropdown-menu">
                                <a class="dropdown-item" href="#">Action</a>
                                <a class="dropdown-item" href="#">Another action</a>
                                <a class="dropdown-item" href="#">Something else here</a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="#">Separated link</a>
                            </div>
                        </div>
                        <b-button type="button" variant="secondary"> Split dropstart </b-button>
                    </DropDown>
                </UICard>

                <UICard title="Active Item">
                    <!-- Active Item -->
                    <div class="btn-group">
                        <b-dropdown text="Active Item">
                            <b-dropdown-item>Regular link</b-dropdown-item>
                            <b-dropdown-item active>Active link</b-dropdown-item>
                            <b-dropdown-item>Another link</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Headers">
                    <!-- Header Item -->
                    <div class="btn-group">
                        <b-dropdown text="Header">
                            <b-dropdown-header> Dropdown header </b-dropdown-header>
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Another action</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Centered dropdowns">
                    <div class="hstack gap-2">
                        <b-dropdown center text="Centered dropdown">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Action two</b-dropdown-item>
                            <b-dropdown-item>Action three</b-dropdown-item>
                        </b-dropdown>

                        <b-dropdown center dropup text="Centered dropup">
                            <b-dropdown-item>Action</b-dropdown-item>
                            <b-dropdown-item>Action two</b-dropdown-item>
                            <b-dropdown-item>Action three</b-dropdown-item>
                        </b-dropdown>
                    </div>
                </UICard>

                <UICard title="Auto close behavior">
                    <div class="hstack gap-2">
                        <div class="btn-group">
                            <b-dropdown text="Default dropdown">
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                            </b-dropdown>
                        </div>

                        <div class="btn-group">
                            <b-dropdown text="Clickable inside" auto-close="inside">
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                            </b-dropdown>
                        </div>

                        <div class="btn-group">
                            <b-dropdown text="Clickable outside" auto-close="outside">
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                            </b-dropdown>
                        </div>

                        <div class="btn-group">
                            <b-dropdown text="Manual close" :auto-close="false">
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                                <b-dropdown-item>Menu item</b-dropdown-item>
                            </b-dropdown>
                        </div>
                    </div>
                </UICard>

                <UICard title="Forms">
                    <!-- Forms -->
                    <b-dropdown text="Form">
                        <b-form class="px-4 py-3">
                            <b-form-group label="Email address" label-for="exampleDropdownFormEmail1" class="mb-3">
                                <b-form-input type="email" id="exampleDropdownFormEmail1" placeholder="email@example.com" />
                            </b-form-group>
                            <b-form-group label="Password" label-for="exampleDropdownFormPassword1" class="mb-3">
                                <b-form-input type="password" id="exampleDropdownFormPassword1" placeholder="Password" />
                            </b-form-group>
                            <b-form-group label-for="dropdownCheck" class="mb-2">
                                <b-form-checkbox id="dropdownCheck"> Remember me </b-form-checkbox>
                            </b-form-group>
                            <b-button type="submit" variant="primary">Sign in</b-button>
                        </b-form>
                        <b-dropdown-divider />
                        <b-dropdown-item>New around here? Sign up</b-dropdown-item>
                        <b-dropdown-item>Forgot password?</b-dropdown-item>
                    </b-dropdown>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import DropDown from '@/components/DropDown.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Icon } from '@iconify/vue';
import { SquareChevronDownIcon } from 'lucide-vue-next';
</script>
