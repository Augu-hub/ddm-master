<template>
    <VerticalLayout>
        <PageTitle title="Form Inputmask" subtitle="Forms" />
        <UICard title="Form Inputmask">
            <b-row>
                <b-col md="6">
                    <form action="#">
                        <b-form-group label="Date" class="mb-3">
                            <InputMask id="dates" mask="99/99/9999" />
                            <span class="fs-13 text-muted">e.g "DD/MM/YYYY"</span>
                        </b-form-group>
                        <b-form-group label="Hour" class="mb-3">
                            <InputMask id="hour" mask="99:99:99" />
                            <span class="fs-13 text-muted">e.g "HH:MM:SS"</span>
                        </b-form-group>
                        <b-form-group label="Date & Hour" class="mb-3">
                            <InputMask id="date-hour" mask="99.99.9999 99:99:99" />
                            <span class="fs-13 text-muted">e.g "DD/MM/YYYY HH:MM:SS"</span>
                        </b-form-group>
                        <b-form-group label="ZIP Code" class="mb-3">
                            <InputMask id="zip" mask="99999-999" />
                            <span class="fs-13 text-muted">e.g "xxxxx-xxx"</span>
                        </b-form-group>
                        <b-form-group label="Crazy Zip Code" class="mb-3">
                            <InputMask id="crazy-zip" mask="9-99-99-99" />
                            <span class="fs-13 text-muted">e.g "x-xx-xx-xx"</span>
                        </b-form-group>
                        <b-form-group label="Money" class="mb-3">
                            <InputMask id="money" mask="999.999.999.999.999,99" />
                            <span class="fs-13 text-muted">e.g "Your money"</span>
                        </b-form-group>
                        <b-form-group label="Money 2" class="mb-3">
                            <InputMask id="money2" mask="#.##9,99" />
                            <span class="fs-13 text-muted">e.g "#.##0,00"</span>
                        </b-form-group>
                    </form>
                </b-col>

                <b-col md="6">
                    <form action="#">
                        <b-form-group label="Telephone" class="mb-3">
                            <InputMask id="telephone" mask="9999-9999" />
                            <span class="fs-13 text-muted">e.g "xxxx-xxxx"</span>
                        </b-form-group>
                        <b-form-group label="Telephone with Code Area" class="mb-3">
                            <InputMask id="telephone-area-code" mask="(99) 9999-9999" />
                            <span class="fs-13 text-muted">e.g "(xx) xxxx-xxxx"</span>
                        </b-form-group>
                        <b-form-group label="US Telephone" class="mb-3">
                            <InputMask id="us-telephone" mask="(999) 999-9999" />
                            <span class="fs-13 text-muted">e.g "(xxx) xxx-xxxx"</span>
                        </b-form-group>
                        <b-form-group label="São Paulo Celphones" class="mb-3">
                            <InputMask id="são-paulo-telephone" mask="(99) 99999-9999" />
                            <span class="fs-13 text-muted">e.g "(xx) xxxxx-xxxx"</span>
                        </b-form-group>
                        <b-form-group label="CPF" class="mb-3">
                            <InputMask id="cpf" mask="999.999.999-99" />
                            <span class="fs-13 text-muted">e.g "xxx.xxx.xxxx-xx"</span>
                        </b-form-group>
                        <b-form-group label="CNPJ" class="mb-3">
                            <InputMask id="cnpj" mask="99.999.999/9999-99" />
                            <span class="fs-13 text-muted">e.g "xx.xxx.xxx/xxxx-xx"</span>
                        </b-form-group>
                        <b-form-group label="IP Address" class="mb-3">
                            <InputMask id="ip-address" mask="999.999.999.999" />
                            <span class="fs-13 text-muted">e.g "xxx.xxx.xxx.xxx"</span>
                        </b-form-group>
                    </form>
                </b-col>
            </b-row>
        </UICard>
    </VerticalLayout>
</template>
<script setup lang="ts">
import InputMask from '@/components/InputMask.vue';
import PageTitle from '@/components/PageTitle.vue';
import UICard from '@/components/UICard.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
</script>
