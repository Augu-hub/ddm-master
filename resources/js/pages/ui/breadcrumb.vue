<template>
    <VerticalLayout>
        <PageTitle title="Breadcrumb" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Example">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item active" aria-current="page">Home</li>
                        </ol>
                    </nav>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Library</li>
                        </ol>
                    </nav>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="With Icons">
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-light mb-2 bg-opacity-50 p-2">
                            <li class="breadcrumb-item active" aria-current="page"><i class="ti ti-smart-home fs-16 me-1"></i>Home</li>
                        </ol>
                    </nav>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-light mb-2 bg-opacity-50 p-2">
                            <li class="breadcrumb-item">
                                <a href="#"><i class="ti ti-smart-home fs-16 me-1"></i>Home</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">Library</li>
                        </ol>
                    </nav>

                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb bg-light mb-0 bg-opacity-50 p-2">
                            <li class="breadcrumb-item">
                                <a href="#"><i class="ti ti-smart-home fs-16 me-1"></i>Home</a>
                            </li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Dividers">
                    <nav style="--osen-breadcrumb-divider: '>'" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>

                    <nav
                        style="
                            --osen-breadcrumb-divider: url(
                                &#34;data:image/svg + xml,
                                %3Csvgxmlns='http://www.w3.org/2000/svg'width='8'height='8'%3E%3Cpathd='M2.5 0L1 1.5 3.5 4 1 6.5 2.5 8l4-4-4-4z'fill='%236c757d'/%3E%3C/svg%3E&#34;
                            );
                        "
                        aria-label="breadcrumb"
                    >
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>

                    <nav style="--osen-breadcrumb-divider: '\ea1c'" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>

                    <nav style="--osen-breadcrumb-divider: '\f4f9'" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>

                    <nav style="--osen-breadcrumb-divider: '\eb0c'" aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0 py-2">
                            <li class="breadcrumb-item"><a href="#">Home</a></li>
                            <li class="breadcrumb-item"><a href="#">Library</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Data</li>
                        </ol>
                    </nav>
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
