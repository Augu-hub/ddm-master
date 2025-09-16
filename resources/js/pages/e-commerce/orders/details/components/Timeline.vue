<template>
    <b-card no-body>
        <b-card-header class="border-bottom">
            <b-card-title tag="h5" class="mb-0">Timeline</b-card-title>
        </b-card-header>
        <b-card-body>
            <b-table-simple responsive borderless class="mb-0">
                <b-tbody>
                    <b-tr>
                        <b-td class="px-0">
                            <p class="fs-14 fw-medium mb-1">4 July (Now)</p>
                            <p class="fs-14 fw-medium mb-0">06:00</p>
                        </b-td>
                        <b-td class="fs-14 fw-medium">
                            <p class="fs-14 fw-medium mb-1">Your package is packed by the courier</p>
                            <p class="fs-14 fw-medium text-muted mb-0">613 Kuhl Avenue Jennifer Lane</p>
                        </b-td>
                    </b-tr>
                    <b-tr>
                        <b-td class="px-0">
                            <p class="fs-14 fw-medium text-muted mb-1">2 July</p>
                            <p class="fs-14 fw-medium text-muted mb-0">10:00</p>
                        </b-td>
                        <b-td>
                            <p class="fs-14 fw-medium text-muted mb-1">Shipment has been created</p>
                            <p class="fs-14 fw-medium text-muted mb-0">613 Kuhl Avenue</p>
                        </b-td>
                    </b-tr>
                    <b-tr>
                        <b-td class="px-0">
                            <p class="fs-14 fw-medium text-muted mb-1">2 July</p>
                            <p class="fs-14 fw-medium text-muted mb-0">04:00</p>
                        </b-td>
                        <b-td>
                            <p class="fs-14 fw-medium text-muted mb-1">Order Placed</p>
                            <p class="fs-14 fw-medium text-muted mb-0">
                                Coderthemes
                                <Icon icon="solar:verified-check-bold" class="text-success align-middle" />
                            </p>
                        </b-td>
                    </b-tr>
                </b-tbody>
            </b-table-simple>
        </b-card-body>
    </b-card>
</template>

<script setup lang="ts">
import { Icon } from '@iconify/vue';
</script>
