<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, computed, watch } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array,
  remainingLeaveBalance: Number,
})

const page = usePage()
const currentUserId = page.props.auth.user.id

const leaveColors = {
  pending: '#fbbf24',
  annual: '#3b82f6',
  sick: '#22c55e',
  personal: '#f59e0b',
  emergency: '#f97316',
  maternity: '#ec4899',
  paternity: '#6366f1',
  rejected: '#b91c1c',
}

const leaveFormSection = ref(null)
const isPolicyModalVisible = ref(false)
const selectedDates = ref([null, null])
const today = new Date()
today.setHours(0, 0, 0, 0)

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: 'sick',
  day_type: 'full',
  start_half_session: '',
  end_half_session: '',
  supporting_document: null,
})

const supportingDocument = ref(null)
function onSupportingDocumentChange(event) {
  supportingDocument.value = event.target.files[0] || null
  form.supporting_document = supportingDocument.value
}

const scrollToLeaveForm = () => {
  if (leaveFormSection.value) {
    leaveFormSection.value.scrollIntoView({ behavior: 'smooth' })
  }
}

const openPolicyModal = () => { isPolicyModalVisible.value = true }
const closePolicyModal = () => { isPolicyModalVisible.value = false }

function toISODateOnly(date) {
  if (!date) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

const calendarEvents = computed(() =>
  (props.highlightedDates || [])
    .filter(ev => ev.user_id === currentUserId)
    .map(ev => ({
      display: 'background',
      start: ev.start,
      end: ev.end
        ? toISODateOnly(new Date(new Date(ev.end + 'T00:00:00').getTime() + 86400000))
        : ev.start,
      color: leaveColors[ev.color_category] || '#9ca3af',
      title: ev.title,
    }))
)

function getSelectionBackground() {
  const [start, end] = selectedDates.value
  if (start && end) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(end.getTime() + 86400000)),
      color: '#2563eb',
    }]
  }
  if (start) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(start.getTime() + 86400000)),
      color: '#ea580c',
    }]
  }
  return []
}

function updateFormDates() {
  const [start, end] = selectedDates.value
  form.start_date = start ? toISODateOnly(start) : ''
  form.end_date = end ? toISODateOnly(end) : ''
}

const handleDateClick = (info) => {
  const clicked = new Date(info.date)
  clicked.setHours(0, 0, 0, 0)
  if (clicked < today) {
    alert('Please select a date that is today or after today.')
    return
  }
  const [start, end] = selectedDates.value
  if (!start) {
    selectedDates.value = [clicked, null]
  } else if (!end) {
    if (clicked >= start) {
      selectedDates.value = [start, clicked]
    } else {
      selectedDates.value = [clicked, null]
    }
  } else {
    selectedDates.value = [clicked, null]
  }
  updateFormDates()
}

const submitApplication = () => {
  if (form.day_type === 'half') {
    if (!form.start_half_session) {
      alert('Please select morning or afternoon session for start date.')
      return
    }
    if (form.end_date && form.start_date !== form.end_date && !form.end_half_session) {
      alert('Please select morning or afternoon session for end date.')
      return
    }
  }
  if (!form.start_date) {
    alert('Please select at least a start date.')
    return
  }
  if (!form.end_date) {
    form.end_date = form.start_date
    selectedDates.value = [new Date(form.start_date), new Date(form.start_date)]
    if (form.day_type === 'half') {
      form.end_half_session = form.start_half_session
    }
  }
  if (form.day_type === 'full') {
    form.start_half_session = null
    form.end_half_session = null
  }

  const startDate = new Date(form.start_date)
  const timeDiff = startDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))
  if (form.leave_type === 'annual' && daysDiff < 7) {
    if (!confirm('Warning: Annual leaves should be requested at least 7 days in advance. Do you still want to submit?')) return
  }
  if (form.leave_type === 'personal' && daysDiff < 3) {
    if (!confirm('Warning: Personal leaves should be requested at least 3 days in advance. Do you still want to submit?')) return
  }

  const formData = new FormData()
  for (const [key, val] of Object.entries(form.data())) {
    formData.append(key, val ?? '')
  }
  if (supportingDocument.value) formData.append('supporting_document', supportingDocument.value)

  router.post(route('leave.store'), formData, {
    preserveScroll: true,
    headers: { 'Content-Type': 'multipart/form-data' },
    onSuccess: () => {
      form.reset()
      form.leave_type = 'sick'
      form.day_type = 'full'
      form.start_half_session = ''
      form.end_half_session = ''
      selectedDates.value = [null, null]
      supportingDocument.value = null
    },
    onError: (errors) => {
      if (errors.message) alert(errors.message)
    }
  })
}

const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
  rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
  pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
}

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), {
    status: newStatus,
  }, { preserveScroll: true })
}

const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), {
      preserveScroll: true,
    })
  }
}

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  selectable: true,
  selectMirror: true,
  height: 'auto',
  events: [...calendarEvents.value, ...getSelectionBackground()],
  dateClick: handleDateClick,
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next',
  },
})

watch([calendarEvents, selectedDates], () => {
  calendarOptions.value.events = [...calendarEvents.value, ...getSelectionBackground()]
}, { deep: true })

const leaveTypeDescriptions = {
  annual: {
    title: "Annual Leave",
    summary: "Planned time off with advance notice",
    details: [
      "For vacations, personal time, or other planned absences",
      "Should be requested at least 7 days in advance",
      "Requires manager approval",
      "Balance accrued based on tenure (typically 15-25 days/year)"
    ],
  },
  sick: {
    title: "Sick Leave",
    summary: "For illness or medical appointments",
    details: [
      "Can be requested with short notice",
      "Doctor's note required for absences longer than 3 days",
      "Typically up to 10 paid days/year",
      "Covers medical appointments and contagious illnesses",
    ],
  },
  personal: {
    title: "Personal Leave",
    summary: "For personal matters requiring 3+ days notice",
    details: [
      "Requires at least 3 days notice when possible",
      "Limited to 5 paid days/year",
      "Examples: Family emergencies, urgent personal business",
      "Not for routine errands or non-urgent matters",
    ],
  },
  emergency: {
    title: "Emergency Leave",
    summary: "Unplanned urgent situations",
    details: [
      "No advance notice required",
      "Typically unpaid after 3 days",
      "Examples: Natural disasters, serious family emergencies",
      "Documentation may be required for extended leave",
    ],
  },
  maternity: {
    title: "Maternity Leave",
    summary: "For new mothers (typically 12+ weeks)",
    details: [
      "Typically 12 weeks paid (varies by location)",
      "Requires 30 days notice when possible",
      "Medical documentation required",
      "Can be combined with other leave types",
    ],
  },
  paternity: {
    title: "Paternity Leave",
    summary: "For new fathers (typically 2-4 weeks)",
    details: [
      "Typically 2-4 weeks paid",
      "Can be taken within 6 months of birth/adoption",
      "Requires 30 days notice when possible",
      "Can be taken intermittently in some cases",
    ],
  },
}

const leaveTypeTags = {
  sick: 'bg-pink-100 text-pink-600',
  casual: 'bg-blue-100 text-blue-600',
  compensatory: 'bg-green-100 text-green-600',
  default: 'bg-gray-100 text-gray-600',
}

const getTagClass = type => leaveTypeTags[type.toLowerCase()] || leaveTypeTags.default

function formatLeaveDays(days) {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
}

const isUploadModalVisible = ref(false)
const uploadFile = ref(null)
const uploadErrors = ref({})
const uploadProcessing = ref(false)
const currentUploadLeaveId = ref(null)

const openUploadModal = (leaveId) => {
  currentUploadLeaveId.value = leaveId
  uploadFile.value = null
  uploadErrors.value = {}
  uploadProcessing.value = false
  isUploadModalVisible.value = true
}

const closeUploadModal = () => {
  isUploadModalVisible.value = false
  currentUploadLeaveId.value = null
  uploadFile.value = null
  uploadErrors.value = {}
  uploadProcessing.value = false
}

function onUploadFileChange(event) {
  uploadFile.value = event.target.files[0]
}

const submitUpload = () => {
  if (!uploadFile.value) {
    uploadErrors.value.supporting_document = 'Please select a file.'
    return
  }
  uploadProcessing.value = true
  const formData = new FormData()
  formData.append('supporting_document', uploadFile.value)

  router.post(route('leave.uploadDocument', { leave_application: currentUploadLeaveId.value }), formData, {
    preserveScroll: true,
    headers: { 'Content-Type': 'multipart/form-data' },
    onSuccess: () => {
      uploadProcessing.value = false
      closeUploadModal()
      router.reload()
    },
    onError: (errors) => {
      uploadProcessing.value = false
      uploadErrors.value = errors
    },
  })
}
</script>

<template>
  <Head title="Leave" />
  <AuthenticatedLayout>
    <div class="p-6 bg-gray-50 min-h-screen">
      <h1 class="text-3xl font-bold text-gray-800 mb-6">Leave</h1>

      <!-- Upcoming/Pending leave requests + controls -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-sm">
          <h2 class="text-lg font-semibold text-gray-700 mb-4">Upcoming/Pending leave requests</h2>
          <div class="space-y-3">
            <div v-for="i in 2" :key="i" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div class="flex items-center gap-4">
                <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
                  <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                </div>
                <div>
                  <p class="font-semibold text-gray-800">06 Apr 2025</p>
                  <p class="text-sm text-gray-500">Casual Leave</p>
                </div>
              </div>
              <p class="text-sm text-gray-600 hidden md:block">Personal Emergency</p>
              <div class="flex items-center gap-2">
                <span class="w-2.5 h-2.5 bg-yellow-400 rounded-full"></span>
                <p class="text-sm font-medium text-yellow-500">Pending</p>
              </div>
            </div>
          </div>
        </div>
        <div class="space-y-3 flex flex-col justify-between">
          <button @click="scrollToLeaveForm" class="w-full text-left bg-blue-600 text-white p-4 rounded-lg shadow-sm hover:bg-blue-700 transition font-medium">
            Apply Leave
          </button>
          <button class="w-full text-left bg-white text-gray-700 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">
            Request Compensatory Off
          </button>
          <button @click="openPolicyModal" class="w-full text-left bg-white text-gray-700 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">
            View Leave Policy
          </button>
        </div>
      </div>

      <!-- Available Leaves Section (optional, add your own if you want) -->

      <!-- Leave Request Form -->
      <div ref="leaveFormSection" class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">New Leave Request</h3>
          <p class="mt-1 text-sm text-gray-500">Select dates and provide details for your leave</p>
        </div>
        <form @submit.prevent="submitApplication" class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6" enctype="multipart/form-data">
          <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
              <InputLabel value="Select Dates" class="text-sm font-medium text-gray-700" />
              <div class="text-xs font-medium px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                <template v-if="form.start_date && !form.end_date">
                  {{ form.start_date }}<span v-if="form.day_type === 'half'">({{ form.start_half_session || 'full day' }})</span>
                </template>
                <template v-else-if="form.start_date && form.end_date">
                  {{ form.start_date }} to {{ form.end_date }}
                </template>
                <template v-else>No dates selected</template>
              </div>
            </div>
            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <FullCalendar :options="calendarOptions" />
            </div>
            <InputError :message="form.errors.start_date" />
          </div>
          <div class="space-y-4">
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-sm font-medium text-gray-700">Remaining Leave Balance</div>
              <div class="text-2xl font-bold text-blue-600 mt-1">{{ props.remainingLeaveBalance }} day{{ props.remainingLeaveBalance !== 1 ? 's' : '' }}</div>
            </div>
            <div>
              <InputLabel for="leave_type" value="Leave Type" class="text-sm font-medium text-gray-700 mb-1" />
              <select id="leave_type" v-model="form.leave_type" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
                <option value="annual">Annual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="personal">Personal Leave</option>
                <option value="emergency">Emergency Leave</option>
                <option value="maternity">Maternity Leave</option>
                <option value="paternity">Paternity Leave</option>
              </select>
              <InputError :message="form.errors.leave_type" />
              <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <h4 class="font-medium text-gray-800 mb-1">{{ leaveTypeDescriptions[form.leave_type].title }}</h4>
                <p class="text-sm text-gray-600 mb-2">{{ leaveTypeDescriptions[form.leave_type].summary }}</p>
              </div>
            </div>
            <div>
              <InputLabel value="Day Type" class="text-sm font-medium text-gray-700 mb-1" />
              <div class="flex space-x-4 mt-1">
                <label class="inline-flex items-center">
                  <input type="radio" v-model="form.day_type" value="full" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-2 text-sm text-gray-700">Full Day</span>
                </label>
                <label class="inline-flex items-center">
                  <input type="radio" v-model="form.day_type" value="half" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                  <span class="ml-2 text-sm text-gray-700">Half Day</span>
                </label>
              </div>
            </div>

            <template v-if="form.day_type === 'half'">
              <div>
                <InputLabel value="Start Session" class="text-sm font-medium text-gray-700 mb-1" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.start_half_session" value="morning" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Morning (AM)</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.start_half_session" value="afternoon" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Afternoon (PM)</span>
                  </label>
                </div>
                <InputError :message="form.errors.start_half_session" />
              </div>
              <div v-if="form.end_date && form.start_date !== form.end_date" class="mt-2">
                <InputLabel value="End Session" class="text-sm font-medium text-gray-700 mb-1" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.end_half_session" value="morning" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Morning (AM)</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input type="radio" v-model="form.end_half_session" value="afternoon" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" />
                    <span class="ml-2 text-sm text-gray-700">Afternoon (PM)</span>
                  </label>
                </div>
                <InputError :message="form.errors.end_half_session" />
              </div>
            </template>

            <div>
              <InputLabel for="reason" value="Reason" class="text-sm font-medium text-gray-700 mb-1" />
              <textarea id="reason" v-model="form.reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Brief explanation for your leave request"></textarea>
              <InputError :message="form.errors.reason" />
            </div>

            <div v-if="form.leave_type === 'sick'">
              <InputLabel for="supporting_document" value="Supporting Document (Optional)" />
              <input id="supporting_document" type="file" @change="onSupportingDocumentChange" accept=".pdf,.jpg,.jpeg,.png" class="mt-1 block w-full" />
              <InputError :message="form.errors.supporting_document" />
            </div>

            <div class="pt-2">
              <PrimaryButton :disabled="form.processing" class="w-full justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
              </PrimaryButton>
            </div>
          </div>
        </form>
      </div>

      <!-- Applied Leaves Table -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <div class="xl:col-span-2 bg-white p-6 rounded-lg shadow-sm">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Applied Leaves</h2>
          </div>

          <div v-if="props.leaveRequests.length === 0" class="p-8 text-center">
            <div class="text-gray-400 text-lg">No leave requests found</div>
          </div>
          <div v-else class="overflow-x-auto">
            <table class="min-w-full text-sm">
              <thead>
                <tr class="text-left text-gray-500 font-medium">
                  <th class="py-2 px-3">Date</th>
                  <th class="py-2 px-3">Type</th>
                  <th class="py-2 px-3">Reason</th>
                  <th class="py-2 px-3">Requested</th>
                  <th class="py-2 px-3 text-center">Document</th>
                  <th class="py-2 px-3 text-right">Actions</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-200">
                <tr v-for="request in props.leaveRequests" :key="request.id" class="text-gray-700 hover:bg-gray-50">
                  <td class="py-3 px-3">
                    <p class="font-semibold">{{ new Date(request.start_date).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                  </td>
                  <td class="py-3 px-3">
                    <span :class="getTagClass(request.leave_type)" class="px-2 py-1 text-xs font-medium rounded-full capitalize">{{ request.leave_type }}</span>
                  </td>
                  <td class="py-3 px-3 max-w-[200px] truncate">{{ request.reason }}</td>
                  <td class="py-3 px-3">
                    <p>{{ new Date(request.created_at).toLocaleDateString(undefined, { day: '2-digit', month: 'short', year: 'numeric' }) }}</p>
                    <p class="text-xs text-gray-500">{{ new Date(request.created_at).toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' }) }}</p>
                  </td>

                  <td class="py-3 px-3 text-center whitespace-nowrap space-y-1">
                    <div>
                      <a v-if="request.supporting_document_path" :href="`/storage/${request.supporting_document_path}`" target="_blank"
                         class="text-indigo-600 hover:underline inline-flex items-center justify-center gap-1" title="View supporting document">
                        View
                      </a>
                      <span v-else class="text-gray-400 text-xs italic">—</span>
                    </div>
                    <div v-if="request.leave_type === 'sick' && ['pending', 'approved'].includes(request.status)">
                      <button @click="openUploadModal(request.id)" class="text-blue-600 hover:underline text-xs mt-1">
                        {{ request.supporting_document_path ? 'Replace Document' : 'Upload Document' }}
                      </button>
                    </div>
                  </td>

                  <td class="py-3 px-3 text-right">
                    <div class="flex flex-col items-end gap-1">
                      <div v-if="canManage && request.status === 'pending'" class="space-x-2">
                        <button @click="updateStatus(request, 'approved')" class="text-green-600 hover:text-green-900 font-semibold">Approve</button>
                        <button @click="updateStatus(request, 'rejected')" class="text-red-600 hover:text-red-900 font-semibold">Reject</button>
                      </div>
                      <button v-else-if="!canManage && request.status === 'pending'" @click="cancelLeave(request)" class="text-red-600 hover:text-red-900 font-semibold">Cancel</button>
                      <button v-else class="text-gray-400 hover:text-gray-600">⋮</button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>

        <!-- Upcoming Holidays (Optional) -->
        <div class="bg-white p-6 rounded-lg shadow-sm">
          <div class="flex justify-between items-center mb-4">
            <h2 class="text-lg font-semibold text-gray-700">Upcoming Holidays</h2>
            <select class="border-gray-300 rounded-md text-sm py-1 focus:ring-blue-500 focus:border-blue-500">
              <option>2025</option>
              <option>2024</option>
            </select>
          </div>
          <div class="space-y-4 max-h-[192px] overflow-y-auto">
            <div v-for="holiday in [
              { date: '01', month: 'Jan 2025', name: `New Year's Day` },
              { date: '21', month: 'Jan 2025', name: 'M.L.K. Jr Day' },
              { date: '18', month: 'Feb 2025', name: `President's Day` },
              { date: '27', month: 'May 2025', name: 'Memorial Day' },
              { date: '25', month: 'Apr 2025', name: 'Easter' },
              { date: '04', month: 'Jul 2025', name: 'Independence Day' }
            ]" :key="holiday.name" class="flex items-center gap-4">
              <div class="bg-gray-100 p-3 rounded-lg text-center w-16 shrink-0">
                <p class="font-bold text-gray-800">{{ holiday.date }}</p>
                <p class="text-xs text-gray-500">{{ holiday.month }}</p>
              </div>
              <p class="font-medium text-gray-600">{{ holiday.name }}</p>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Leave Policy Modal -->
    <div v-if="isPolicyModalVisible" @click.self="closePolicyModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
      <div class="bg-white rounded-xl shadow-2xl max-w-2xl w-full max-h-[90vh] flex flex-col transform transition-all duration-300 scale-95" :class="{ 'scale-100': isPolicyModalVisible }">
        <div class="p-5 border-b flex justify-between items-center">
          <h3 class="text-xl font-semibold text-gray-800">Company Leave Policy</h3>
          <button @click="closePolicyModal" class="text-gray-400 hover:text-gray-800 text-3xl leading-none">×</button>
        </div>
        <div class="p-6 overflow-y-auto space-y-6">
          <div v-for="(policy, type) in leaveTypeDescriptions" :key="type" class="pb-4 border-b last:border-b-0">
            <h4 class="text-lg font-semibold text-gray-700 mb-2 capitalize">{{ policy.title }}</h4>
            <p class="text-sm text-gray-600 mb-3 italic">"{{ policy.summary }}"</p>
            <ul class="list-disc list-inside space-y-1.5 text-sm text-gray-700">
              <li v-for="(detail, index) in policy.details" :key="index">{{ detail }}</li>
            </ul>
          </div>
        </div>
        <div class="p-4 bg-gray-50 text-right rounded-b-xl">
          <button @click="closePolicyModal" class="bg-gray-200 text-gray-800 px-5 py-2 rounded-lg hover:bg-gray-300 font-semibold text-sm">Close</button>
        </div>
      </div>
    </div>

    <!-- Upload Document Modal -->
    <div v-if="isUploadModalVisible" @click.self="closeUploadModal" class="fixed inset-0 bg-black bg-opacity-60 flex justify-center items-center z-50 p-4 transition-opacity duration-300">
      <form @submit.prevent="submitUpload" enctype="multipart/form-data" class="bg-white rounded-xl shadow-2xl max-w-md w-full p-6 space-y-4">
        <h3 class="text-lg font-semibold text-gray-800 mb-2">Upload Supporting Document</h3>
        <input id="upload_file" type="file" @change="onUploadFileChange" accept=".pdf,.jpg,.jpeg,.png" required class="w-full" />
        <div v-if="uploadErrors.supporting_document" class="text-sm text-red-600">{{ uploadErrors.supporting_document }}</div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="closeUploadModal" class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-100">Cancel</button>
          <PrimaryButton type="submit" :disabled="uploadProcessing">{{ uploadProcessing ? 'Uploading...' : 'Upload' }}</PrimaryButton>
        </div>
      </form>
    </div>

  </AuthenticatedLayout>
</template>

<style>
.fc {
  @apply text-gray-800;
}
.fc .fc-toolbar.fc-header-toolbar { margin-bottom: 0.5em; }
.fc .fc-toolbar-title { font-size: 1.1em; @apply font-semibold; }
.fc .fc-button { @apply bg-white border-gray-300 text-gray-700 hover:bg-gray-50; }
.fc .fc-button-primary:not(:disabled).fc-button-active { @apply bg-blue-600 text-white; }
.fc .fc-daygrid-day-frame { @apply min-h-[2em]; }
.fc .fc-daygrid-day-number { @apply p-1 text-gray-800 hover:text-blue-600; }
.fc .fc-daygrid-day.fc-day-today { @apply bg-blue-50; }
.fc .fc-daygrid-day-top { @apply flex-col items-start; }
.fc .fc-col-header-cell { @apply bg-gray-50; }
.fc .fc-col-header-cell-cushion { @apply text-gray-700 text-sm font-medium py-2; }
.fc .fc-daygrid-day-events { @apply mt-1; }
.fc .fc-event { @apply text-white text-xs rounded p-1 mb-1; }
.fc .fc-daygrid-day.fc-day-disabled { @apply bg-gray-50; }
.fc .fc-daygrid-day.fc-day-disabled .fc-daygrid-day-number { @apply text-gray-400; }
select option { position: relative; }
</style>
