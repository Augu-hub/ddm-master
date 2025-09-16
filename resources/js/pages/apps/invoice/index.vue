
<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { invoices, statistics } from '@/pages/apps/invoice/components/data';
import { Icon } from '@iconify/vue';

import mastercard from '@/images/cards/mastercard.svg';
import visa from '@/images/cards/visa.svg';
import { ref } from 'vue';
import { currency } from '../../../helpers';
import { toSentenceCase } from '../../../helpers/change-casing';

const currentPage = ref(1);
</script>
