

<script setup lang="ts">
import PageTitle from '@/components/PageTitle.vue';
import { getFirstCharacter, toSentenceCase } from '@/helpers/change-casing';
import VerticalLayout from '@/layouts/VerticalLayout.vue';
import AppCard from '@/pages/apps/file-manager/components/AppCard.vue';
import Sidebar from '@/pages/apps/file-manager/components/Sidebar.vue';
import { apps, recentFileActivityTable } from '@/pages/apps/file-manager/components/data';
</script>
