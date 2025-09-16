<template>
    <VerticalLayout>
        <PageTitle title="Links" subtitle="Base UI" />
        <b-row>
            <b-col xl="6">
                <UICard title="Colored links">
                    <p><Link href="#" class="link-primary">Primary link</Link></p>
                    <p><Link href="#" class="link-secondary">Secondary link</Link></p>
                    <p><Link href="#" class="link-success">Success link</Link></p>
                    <p><Link href="#" class="link-danger">Danger link</Link></p>
                    <p><Link href="#" class="link-warning">Warning link</Link></p>
                    <p><Link href="#" class="link-info">Info link</Link></p>
                    <p><Link href="#" class="link-light">Light link</Link></p>
                    <p><Link href="#" class="link-dark">Dark link</Link></p>
                    <p class="mb-0"><Link href="#" class="link-body-emphasis">Emphasis link</Link></p>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Link utilities">
                    <p>
                        <Link
                            href="#"
                            class="link-primary text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Primary link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-secondary text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Secondary link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-success text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Success link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-danger text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Danger link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-warning text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Warning link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-info text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Info link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-light text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Light link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-dark text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover"
                            >Dark link</Link
                        >
                    </p>
                    <p>
                        <Link
                            href="#"
                            class="link-body-emphasis text-decoration-underline link-offset-2 link-underline-opacity-25 link-underline-opacity-75-hover"
                            >Emphasis link</Link
                        >
                    </p>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Link opacity">
                    <p><Link class="link-opacity-10" href="#">Link opacity 10</Link></p>
                    <p><Link class="link-opacity-25" href="#">Link opacity 25</Link></p>
                    <p><Link class="link-opacity-50" href="#">Link opacity 50</Link></p>
                    <p><Link class="link-opacity-75" href="#">Link opacity 75</Link></p>
                    <p class="mb-0"><Link class="link-opacity-100" href="#">Link opacity 100</Link></p>
                </UICard>
            </b-col>

            <b-col xl="6">
                <UICard title="Link hover opacity">
                    <p><Link class="link-opacity-10-hover" href="#">Link hover opacity 10</Link></p>
                    <p><Link class="link-opacity-25-hover" href="#">Link hover opacity 25</Link></p>
                    <p><Link class="link-opacity-50-hover" href="#">Link hover opacity 50</Link></p>
                    <p><Link class="link-opacity-75-hover" href="#">Link hover opacity 75</Link></p>
                    <p class="mb-0"><Link class="link-opacity-100-hover" href="#">Link hover opacity 100</Link></p>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Underline color">
                    <p><Link href="#" class="text-decoration-underline link-underline-primary">Primary underline</Link></p>
                    <p><Link href="#" class="text-decoration-underline link-underline-secondary">Secondary underline</Link></p>
                    <p><Link href="#" class="text-decoration-underline link-underline-success">Success underline</Link></p>
                    <p><Link href="#" class="text-decoration-underline link-underline-danger">Danger underline</Link></p>
                    <p><Link href="#" class="text-decoration-underline link-underline-warning">Warning underline</Link></p>
                    <p><Link href="#" class="text-decoration-underline link-underline-info">Info underline</Link></p>
                    <p><Link href="#" class="text-decoration-underline link-underline-light">Light underline</Link></p>
                    <p class="mb-0"><Link href="#" class="text-decoration-underline link-underline-dark">Dark underline</Link></p>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Underline opacity">
                    <p>
                        <Link class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-0" href="#"
                            >Underline opacity 0</Link
                        >
                    </p>
                    <p>
                        <Link class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-10" href="#"
                            >Underline opacity 10</Link
                        >
                    </p>
                    <p>
                        <Link class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-25" href="#"
                            >Underline opacity 25</Link
                        >
                    </p>
                    <p>
                        <Link class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-50" href="#"
                            >Underline opacity 50</Link
                        >
                    </p>
                    <p>
                        <Link class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-75" href="#"
                            >Underline opacity 75</Link
                        >
                    </p>
                    <p class="mb-0">
                        <Link class="text-decoration-underline link-offset-2 link-underline link-underline-opacity-100" href="#"
                            >Underline opacity 100</Link
                        >
                    </p>
                </UICard>
            </b-col>
        </b-row>

        <b-row>
            <b-col xl="6">
                <UICard title="Underline offset">
                    <p><Link href="#">Default link</Link></p>
                    <p><Link class="text-decoration-underline link-offset-1" href="#">Offset 1 link</Link></p>
                    <p><Link class="text-decoration-underline link-offset-2" href="#">Offset 2 link</Link></p>
                    <p class="mb-0"><Link class="text-decoration-underline link-offset-3" href="#">Offset 3 link</Link></p>
                </UICard>
            </b-col>
            <b-col xl="6">
                <UICard title="Hover variants">
                    <Link
                        class="link-offset-2 link-offset-3-hover text-decoration-underline link-underline link-underline-opacity-0 link-underline-opacity-75-hover"
                        href="#"
                    >
                        Underline opacity 0
                    </Link>
                </UICard>
            </b-col>
        </b-row>
    </VerticalLayout>
</template>
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { Link } from '@inertiajs/vue3';
</script>
