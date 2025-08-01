<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router } from '@inertiajs/vue3'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { ref } from 'vue'
import { watch } from 'vue'


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

const isEditModalVisible = ref(false)
const editingRequest = ref(null)
const editingReason = ref('')
const editProcessing = ref(false)

function openEditModal(req) {
  editingRequest.value = req
  editingReason.value = req.reason
  isEditModalVisible.value = true
}
function closeEditModal() {
  isEditModalVisible.value = false
  editingRequest.value = null
  editingReason.value = ''
  editProcessing.value = false
}

function submitEditReason() {
  if (!editingRequest.value) return
  editProcessing.value = true
  router.patch(
    route('leave.updateReason', { leave_application: editingRequest.value.id }),
    { reason: editingReason.value },
    {
      preserveScroll: true,
      onSuccess: () => {
        editProcessing.value = false
        closeEditModal()
        router.reload()
      },
      onError: () => { editProcessing.value = false }
    }
  )
}
function cancelRequest(req) {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: req.id }), {
      preserveScroll: true,
    })
  }
}

const isReasonModalVisible = ref(false)
const rejectionReasonToShow = ref('')

function showRejectionReason(reason) {
  rejectionReasonToShow.value = reason
  isReasonModalVisible.value = true
}

function closeReasonModal() {
  isReasonModalVisible.value = false
  rejectionReasonToShow.value = ''
}

watch(isReasonModalVisible, (visible) => {
  if (visible) {
    document.body.style.overflow = 'hidden'
  } else {
    document.body.style.overflow = ''
  }
})

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
              <th class="p-3">Actions</th>
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
              <td class="p-3 flex items-center gap-2">
  <span :class="statusClass(req.status)" class="inline-block rounded px-2 py-1 text-xs font-semibold">
    {{ req.status.charAt(0).toUpperCase() + req.status.slice(1) }}
  </span>

  <!-- Show button for rejected status only -->
  <button
    v-if="req.status === 'rejected' && req.rejection_reason"
    @click="showRejectionReason(req.rejection_reason)"
    type="button"
    class="ml-2 px-2 py-1 text-xs text-red-700 bg-red-100 hover:bg-red-200 rounded"
  >
    View Reason
  </button>
</td>

              <td class="p-3">{{ new Date(req.created_at).toLocaleString() }}</td>
              <td class="p-3">
  <button
    v-if="req.status === 'pending'"
    @click="openEditModal(req)"
    class="text-blue-600 hover:underline font-semibold text-sm mr-2"
  >
    Edit
  </button>
  <button
    v-if="req.status === 'pending'"
    @click="cancelRequest(req)"
    class="text-red-600 hover:underline font-semibold text-sm"
  >
    Cancel
  </button>
  <span v-else class="text-gray-400">-</span>
</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Edit Reason Modal -->
<div
  v-if="isEditModalVisible"
  @click.self="closeEditModal"
  class="fixed inset-0 bg-black bg-opacity-60 z-50 flex items-center justify-center p-4"
>
  <form
    @submit.prevent="submitEditReason"
    class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 flex flex-col gap-4"
  >
    <h2 class="text-lg font-semibold mb-1">Edit Reason</h2>
    <p class="text-sm text-gray-600 mb-2">Update the reason for your leave application:</p>
    <textarea
      v-model="editingReason"
      rows="4"
      required
      class="w-full border rounded px-3 py-2"
      :disabled="editProcessing"
      maxlength="500"
    ></textarea>
    <div class="flex gap-2 justify-end pt-2">
      <button type="button" @click="closeEditModal"
        class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300">Cancel</button>
      <PrimaryButton type="submit" :disabled="editProcessing">
        {{ editProcessing ? 'Saving...' : 'Save' }}
      </PrimaryButton>
    </div>
  </form>
</div>

      <!-- Rejection Reason Modal -->
<teleport to="body">
  <div
    v-if="isReasonModalVisible"
    @click.self="closeReasonModal"
    class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
  >
    <div
      class="bg-white rounded-lg max-w-md w-full p-6 shadow-lg overflow-auto max-h-[70vh]"
      role="dialog"
      aria-modal="true"
      aria-labelledby="modal-title"
    >
      <h3 id="modal-title" class="text-lg font-semibold mb-4 text-red-700">
        Reason for Rejection
      </h3>
      <p class="whitespace-pre-line text-gray-800 break-words">
        {{ rejectionReasonToShow }}
      </p>
      <div class="mt-6 flex justify-end">
        <button
          @click="closeReasonModal"
          class="px-4 py-2 bg-gray-200 rounded hover:bg-gray-300 font-semibold"
        >
          Close
        </button>
      </div>
    </div>
  </div>
</teleport>



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
