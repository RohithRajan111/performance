<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { ref } from 'vue'

// Props
const props = defineProps({
  leaveRequests: Object,
  filters: Object,
})

const statusOptions = [
  { label: 'All', value: '' },
  { label: 'Pending', value: 'pending' },
  { label: 'Approved', value: 'approved' },
  { label: 'Rejected', value: 'rejected' },
]

const leaveTypeOptions = [
  { label: 'All', value: '' },
  { label: 'Annual', value: 'annual' },
  { label: 'Sick', value: 'sick' },
  { label: 'Personal', value: 'personal' },
  { label: 'Emergency', value: 'emergency' },
  { label: 'Maternity', value: 'maternity' },
  { label: 'Paternity', value: 'paternity' },
]

// Current filters reactive refs
const statusFilter = ref(props.filters.status || '')
const leaveTypeFilter = ref(props.filters.leave_type || '')

// Watch filters and reload page with query params
function applyFilters() {
  router.get(route('leave.fullRequests'), {
    status: statusFilter.value || undefined,
    leave_type: leaveTypeFilter.value || undefined,
  }, { preserveState: true, replace: true })
}

// Helper to display capitalized status with badge color
const statusClass = (status) => {
  return {
    pending: 'bg-yellow-100 text-yellow-800',
    approved: 'bg-green-100 text-green-800',
    rejected: 'bg-red-100 text-red-800',
  }[status] || 'bg-gray-100 text-gray-800'
}
</script>

<template>
  <Head title="All Leave Requests" />
  <AuthenticatedLayout>
    <div class="p-6 max-w-5xl mx-auto">
      <h1 class="text-3xl font-bold mb-6">My Leave Requests</h1>

      <!-- Filters -->
      <div class="flex flex-wrap gap-4 mb-6">
        <select v-model="statusFilter" @change="applyFilters" class="rounded border-gray-300 px-4 py-2 shadow-sm">
          <option v-for="opt in statusOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
        <select v-model="leaveTypeFilter" @change="applyFilters" class="rounded border-gray-300 px-4 py-2 shadow-sm">
          <option v-for="opt in leaveTypeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
        </select>
      </div>

      <!-- Leave Requests Table -->
      <div class="overflow-auto bg-white rounded shadow">
        <table class="min-w-full text-left text-sm text-gray-700">
          <thead class="bg-gray-50">
            <tr>
              <th class="p-3">Start Date</th>
              <th class="p-3">End Date</th>
              <th class="p-3">Type</th>
              <th class="p-3">Reason</th>
              <th class="p-3">Status</th>
              <th class="p-3">Requested At</th>
            </tr>
          </thead>
          <tbody>
            <tr v-if="leaveRequests.data.length === 0">
              <td colspan="6" class="p-4 text-center text-gray-500">No leave requests found.</td>
            </tr>
            <tr v-for="req in leaveRequests.data" :key="req.id" class="border-t hover:bg-gray-50">
              <td class="p-3">{{ new Date(req.start_date).toLocaleDateString() }}</td>
              <td class="p-3">{{ req.end_date ? new Date(req.end_date).toLocaleDateString() : '-' }}</td>
              <td class="p-3 capitalize">{{ req.leave_type }}</td>
              <td class="p-3 truncate max-w-[300px]" :title="req.reason">{{ req.reason }}</td>
              <td class="p-3">
                <span :class="statusClass(req.status)" class="inline-block rounded px-2 py-1 text-xs font-semibold">
                  {{ req.status.charAt(0).toUpperCase() + req.status.slice(1) }}
                </span>
              </td>
              <td class="p-3">{{ new Date(req.created_at).toLocaleString() }}</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div class="mt-6 flex justify-center">
        <nav class="inline-flex -space-x-px rounded-md shadow-sm" aria-label="Pagination">
          <template v-for="(link, index) in leaveRequests.links" :key="index">
            <span v-if="!link.url" class="relative inline-flex items-center rounded-l-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm font-medium text-gray-500 cursor-default"
              v-html="link.label"></span>
            <a v-else
              :href="link.url"
              class="relative inline-flex items-center border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
              :class="{ 'bg-blue-600 text-white': link.active }"
              v-html="link.label"></a>
          </template>
        </nav>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
