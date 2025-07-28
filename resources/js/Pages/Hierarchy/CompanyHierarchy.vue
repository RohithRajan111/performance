<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import BalkanOrgChart from '@/Components/BalkanOrgChart.vue'; // <-- Import our new component
import { Head } from '@inertiajs/vue3'
import { ref } from 'vue'

const props = defineProps({
  reportingNodes: Array,
  leaveFlowNodes: Array
});

const activeTab = ref('reporting');
</script>

<template>
  <Head title="Company Hierarchy" />
  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Company Hierarchy</h2>
    </template>
    <div class="py-12">
      <div class="max-w-full mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
          
          <!-- Tab Navigation (Unchanged) -->
          <div class="border-b border-gray-200">
            <nav class="-mb-px flex" aria-label="Tabs">
              <button @click="activeTab = 'reporting'" :class="[activeTab === 'reporting' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300']" class="w-1/2 py-4 px-1 text-center border-b-2 font-medium text-sm focus:outline-none transition-colors duration-200">
                Reporting Structure
              </button>
            </nav>
          </div>
          
          <div class="p-6">
            <!-- Reporting Chart -->
            <div v-show="activeTab === 'reporting'">
              <div v-if="props.reportingNodes && props.reportingNodes.length">
                <!-- Use the new powerful component -->
                <BalkanOrgChart :nodes="props.reportingNodes" />
              </div>
              <div v-else class="text-center text-gray-500 py-10">No reporting structure to display.</div>
            </div>
            <!-- Leave Flow Chart (can be adapted later) -->
            <div v-show="activeTab === 'leave'">
              <div class="text-center text-gray-500 py-10">Leave approval flow chart not yet implemented.</div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>