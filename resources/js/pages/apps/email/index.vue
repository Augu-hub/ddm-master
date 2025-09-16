

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import seller6 from '@/images/sellers/s-6.svg';
import avatar2 from '@/images/users/avatar-2.jpg';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import { emails } from '@/pages/apps/email/components/data';
import EmailSidebar from '@/pages/apps/email/components/EmailSidebar.vue';
import { Icon } from '@iconify/vue';
import { ref } from 'vue';

const isMailDetailModalOpen = ref(false);
const toggleMailDetailModal = () => {
    isMailDetailModalOpen.value = !isMailDetailModalOpen.value;
};
</script>
