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
  if (supportingDocument.value) {
    formData.append('supporting_document', supportingDocument.value)
  }

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

// Update calendar events reactively on changes
watch([calendarEvents, selectedDates], () => {
  calendarOptions.value.events = [...calendarEvents.value, ...getSelectionBackground()]
}, { deep: true })

// --- Upload Document Modal State and Methods ---
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
      <!-- Top Section Grid -->
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
          <button @click="scrollToLeaveForm" class="w-full text-left bg-blue-600 text-white p-4 rounded-lg shadow-sm hover:bg-blue-700 transition font-medium">Apply Leave</button>
          <button class="w-full text-left bg-white text-gray-700 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">Request Compensatory Off</button>
          <button @click="openPolicyModal" class="w-full text-left bg-white text-gray-700 p-4 rounded-lg shadow-sm hover:bg-gray-100 transition font-medium">View Leave Policy</button>
        </div>
      </div>
      <!-- Available Leaves Section -->
      <div class="mb-6">
        <h2 class="text-lg font-semibold text-gray-700 mb-4">Available Leaves</h2>
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-6">
          <div v-for="leave in [
              {name: 'Casual Leave', color: 'text-blue-500', available: 5, consumed: 7, annual: 12, progress: 58}, 
              {name: 'Sick Leave', color: 'text-red-500', available: 5, consumed: 2, annual: 6, progress: 33}, 
              {name: 'Compensatory Leave', color: 'text-green-500', available: 5, consumed: 5, annual: 10, progress: 50}, 
              {name: 'Special Holiday', color: 'text-purple-500', available: 5, consumed: 5, annual: 10, progress: 50}, 
              {name: 'Loss of Pay', color: 'text-gray-400', available: 5, consumed: 5, annual: 10, progress: 50}
            ]" :key="leave.name" class="bg-white p-4 rounded-lg shadow-sm text-center">
            <div class="relative w-24 h-24 mx-auto mb-2">
              <svg class="w-full h-full" viewBox="0 0 36 36"><path class="text-gray-200" stroke-width="3" fill="none" stroke="currentColor" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path><path :class="leave.color" stroke-width="3" fill="none" stroke-linecap="round" stroke="currentColor" :stroke-dasharray="`${leave.progress}, 100`" d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"></path></svg>
              <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-2xl font-bold text-gray-800">{{ leave.available }}</span>
                <span class="text-xs text-gray-500">Days</span>
              </div>
            </div>
            <p class="font-semibold text-gray-600 text-sm">{{ leave.name }}</p>
            <div class="text-xs text-gray-400 mt-1">Consumed: {{ String(leave.consumed).padStart(2,'0') }} / Annual: {{ leave.annual }}</div>
          </div>
        </div>
      </div>
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
              <!-- You may insert leaveTypeDescriptions preview here if you wish -->
            </div>
            <div>
              <InputLabel value="Day Type" class="text-sm font-medium text-gray-700 mb-1" />
              <div class="flex space-x-4 mt-1">
                <label class="inline-flex items-center"><input type="radio" v-model="form.day_type" value="full" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" /><span class="ml-2 text-sm text-gray-700">Full Day</span></label>
                <label class="inline-flex items-center"><input type="radio" v-model="form.day_type" value="half" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300" /><span class="ml-2 text-sm text-gray-700">Half Day</span></label>
              </div>
            </div>
            <template v-if="form.day_type === 'half'">
              <!-- Add half day session inputs here if you have -->
            </template>
            <div>
              <InputLabel for="reason" value="Reason" class="text-sm font-medium text-gray-700 mb-1" />
              <textarea id="reason" v-model="form.reason" rows="3" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm" placeholder="Brief explanation for your leave request"></textarea>
              <InputError :message="form.errors.reason" />
            </div>
            <!-- Supporting Document input only for sick leave -->
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
      <!-- ...[rest of your original template]... (Applied leaves table, modals, etc.) -->
    </div>
  </AuthenticatedLayout>
</template>


<style scoped>
/* FullCalendar Custom Styles (No changes needed) */
:deep(.fc) {
  @apply text-sm text-slate-700;
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
:deep(.fc .fc-toolbar.fc-header-toolbar) {
  @apply mb-4;
}
:deep(.fc .fc-toolbar-title) {
  @apply text-lg font-bold text-slate-800;
}
:deep(.fc .fc-button) {
  @apply bg-white border-slate-300 text-slate-600 hover:bg-slate-50 focus:ring-0 focus:outline-none focus:border-slate-400 shadow-sm transition-colors;
}
:deep(.fc .fc-button-primary.fc-button-active){
  @apply bg-slate-100 text-slate-800;
}
:deep(.fc .fc-button .fc-icon) {
    font-size: 1.25em;
}
:deep(.fc .fc-daygrid-day.fc-day-today) {
  @apply bg-blue-50;
}
:deep(.fc .fc-daygrid-day.fc-day-today .fc-daygrid-day-number) {
  @apply text-blue-600 font-bold;
}
:deep(.fc .fc-daygrid-day-number) {
  @apply p-1.5 text-slate-600;
}
:deep(.fc .fc-col-header-cell-cushion) {
  @apply text-slate-500 font-semibold py-2.5;
}
:deep(.fc .fc-day-disabled) {
    @apply bg-slate-50 opacity-70;
}
:deep(.fc .fc-daygrid-event) {
    @apply border-none;
}
:deep(.fc-bg-event) {
    opacity: 0.8;
}

</style>

