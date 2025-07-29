<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, watch, onMounted, computed } from 'vue' // It's good practice to list all imports
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'


const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array,
  remainingLeaveBalance: Number,
})

// --- FIX START ---
// The usePage() hook must be called to get access to the page props (like auth, ziggy, etc.)
const page = usePage();
// --- FIX END ---

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

const selectedDates = ref([null, null])
const today = new Date()
today.setHours(0, 0, 0, 0)

const form = useForm({
  start_date: '',
  end_date: '',
  reason: '',
  leave_type: 'annual',
  day_type: 'full',
  start_half_session: '',
  end_half_session: '',
})

function toISODateOnly(date) {
  if (!date) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

// Now `page` is defined and can be used to safely access props.
// Using a computed property is recommended for reactivity.
const currentUserId = computed(() => page.props.auth.user.id)

function getBackgroundEvents() {
  return (props.highlightedDates || []).map(ev => ({
    display: 'background',
    start: ev.start,
    end: ev.end ? toISODateOnly(new Date(new Date(ev.end + 'T00:00:00').getTime() + 86400000)) : ev.start,
    color: leaveColors[ev.color_category] || '#9ca3af',
    title: ev.title,
  }))
}

function getSelectionBackground() {
  const [start, end] = selectedDates.value
  if (start && end) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(end.getTime() + 24 * 60 * 60 * 1000)),
      color: '#2563eb',
    }]
  }
  if (start) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(start.getTime() + 24 * 60 * 60 * 1000)),
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
    if (!confirm(`Warning: Annual leaves should be requested at least 7 days in advance. Do you still want to submit?`)) {
      return
    }
  }
  if (form.leave_type === 'personal' && daysDiff < 3) {
    if (!confirm(`Warning: Personal leaves should be requested at least 3 days in advance. Do you still want to submit?`)) {
      return
    }
  }

  form.post(route('leave.store'), {
    preserveScroll: true,
    onSuccess: () => {
    form.reset()
      form.leave_type = 'annual'
      form.day_type = 'full'
      form.start_half_session = ''
      form.end_half_session = ''
      selectedDates.value = [null, null]
    },
    onError: (errors) => {
      console.error('Leave submission errors:', errors)
      if (errors.message) {
        alert(errors.message)
      }
    }
  })
}

const formatLeaveDays = (days) => {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
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
  height: 350,
  aspectRatio: 1.3,
  events: [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ],
  dateClick: handleDateClick,
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next'
  },
})

watch(selectedDates, () => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
}, { deep: true })

watch(() => props.highlightedDates, () => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
}, { deep: true })

onMounted(() => {
  calendarOptions.value.events = [
    ...getBackgroundEvents(),
    ...getSelectionBackground(),
  ]
})

const leaveTypeDescriptions = {
  annual: {
    title: "Annual Leave",
    summary: "Planned time off with advance notice",
    details: [
      "✓ For vacations, personal time, or other planned absences",
      "✓ Should be requested at least 7 days in advance",
      "✓ Requires manager approval",
      "✓ Balance accrued based on tenure (typically 15-25 days/year)"
    ]
  },
  sick: {
    title: "Sick Leave",
    summary: "For illness or medical appointments",
    details: [
      "✓ Can be requested with short notice",
      "✓ Doctor's note required for absences longer than 3 days",
      "✓ Typically up to 10 paid days/year",
      "✓ Covers medical appointments and contagious illnesses"
    ]
  },
  personal: {
    title: "Personal Leave",
    summary: "For personal matters requiring 3+ days notice",
    details: [
      "✓ Requires at least 3 days notice when possible",
      "✓ Limited to 5 paid days/year",
      "✓ Examples: Family emergencies, urgent personal business",
      "✓ Not for routine errands or non-urgent matters"
    ]
  },
  emergency: {
    title: "Emergency Leave",
    summary: "Unplanned urgent situations",
    details: [
      "✓ No advance notice required",
      "✓ Typically unpaid after 3 days",
      "✓ Examples: Natural disasters, serious family emergencies",
      "✓ Documentation may be required for extended leave"
    ]
  },
  maternity: {
    title: "Maternity Leave",
    summary: "For new mothers (typically 12+ weeks)",
    details: [
      "✓ Typically 12 weeks paid (varies by location)",
      "✓ Requires 30 days notice when possible",
      "✓ Medical documentation required",
      "✓ Can be combined with other leave types"
    ]
  },
  paternity: {
    title: "Paternity Leave",
    summary: "For new fathers (typically 2-4 weeks)",
    details: [
      "✓ Typically 2-4 weeks paid",
      "✓ Can be taken within 6 months of birth/adoption",
      "✓ Requires 30 days notice when possible",
      "✓ Can be taken intermittently in some cases"
    ]
  }
}
</script>

<template>
  <Head title="Leave Applications" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="text-2xl font-bold text-gray-800">Leave Applications</h2>
    </template>

    <div class="py-6 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
      <!-- Leave Request Form -->
      <div v-if="!canManage" class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">New Leave Request</h3>
          <p class="mt-1 text-sm text-gray-500">Select dates and provide details for your leave</p>
        </div>


        <form @submit.prevent="submitApplication" class="grid grid-cols-1 lg:grid-cols-3 gap-6 p-6">
          <!-- Calendar Section -->
          <div class="lg:col-span-2 space-y-4">
            <div class="flex items-center justify-between">
              <InputLabel value="Select Dates" class="text-sm font-medium text-gray-700" />
              <div class="text-xs font-medium px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                <template v-if="form.start_date && !form.end_date">
                  {{ form.start_date }}
                  <span v-if="form.day_type === 'half'">({{ form.start_half_session || 'full day' }})</span>
                </template>
                <template v-else-if="form.start_date && form.end_date">
                  {{ form.start_date }} to {{ form.end_date }}
                </template>
                <template v-else>
                  No dates selected
                </template>
              </div>
            </div>

            <div class="border border-gray-200 rounded-lg overflow-hidden">
              <FullCalendar :options="calendarOptions" class="h-[350px]" />
            </div>
            <InputError :message="form.errors.start_date" />
          </div>

          <!-- Form Fields Section -->
          <div class="space-y-4">
            <!-- Leave Balance -->
            <div class="bg-blue-50 p-4 rounded-lg">
              <div class="text-sm font-medium text-gray-700">Remaining Leave Balance</div>
              <div class="text-2xl font-bold text-blue-600 mt-1">
                {{ props.remainingLeaveBalance }} day{{ props.remainingLeaveBalance !== 1 ? 's' : '' }}
              </div>
            </div>

            <!-- Leave Type -->
            <div>
              <InputLabel for="leave_type" value="Leave Type" class="text-sm font-medium text-gray-700 mb-1" />
              <select
  id="leave_type"
  v-model="form.leave_type"
  required
  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
>
  <option value="annual">Annual Leave (Planned time off, 7+ days notice)</option>
  <option value="sick">Sick Leave (Illness/medical appointments)</option>
  <option value="personal">Personal Leave (Personal matters, 3+ days notice)</option>
  <option value="emergency">Emergency Leave (Unplanned urgent situations)</option>
  <option value="maternity">Maternity Leave (For new mothers)</option>
  <option value="paternity">Paternity Leave (For new fathers)</option>
</select>
              <InputError :message="form.errors.leave_type" />

              <!-- Detailed Leave Description -->
              <div class="mt-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <h4 class="font-medium text-gray-800 mb-1">{{ leaveTypeDescriptions[form.leave_type].title }}</h4>
                <p class="text-sm text-gray-600 mb-2">{{ leaveTypeDescriptions[form.leave_type].summary }}</p>
                <ul class="text-xs space-y-1 text-gray-600">
                  <li v-for="(detail, index) in leaveTypeDescriptions[form.leave_type].details" :key="index">
                    {{ detail }}
                  </li>
                </ul>
              </div>
            </div>

            <!-- Day Type -->
            <div>
              <InputLabel value="Day Type" class="text-sm font-medium text-gray-700 mb-1" />
              <div class="flex space-x-4 mt-1">
                <label class="inline-flex items-center">
                  <input
                    type="radio"
                    v-model="form.day_type"
                    value="full"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Full Day</span>
                </label>
                <label class="inline-flex items-center">
                  <input
                    type="radio"
                    v-model="form.day_type"
                    value="half"
                    class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                  />
                  <span class="ml-2 text-sm text-gray-700">Half Day</span>
                </label>
              </div>
            </div>

            <!-- Half Day Options -->
            <template v-if="form.day_type === 'half'">
              <div>
                <InputLabel value="Start Session" class="text-sm font-medium text-gray-700 mb-1" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input
                      type="radio"
                      v-model="form.start_half_session"
                      value="morning"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Morning</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input
                      type="radio"
                      v-model="form.start_half_session"
                      value="afternoon"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Afternoon</span>
                  </label>
                </div>
                <InputError :message="form.errors.start_half_session" />
              </div>

              <div v-if="form.end_date && form.start_date !== form.end_date">
                <InputLabel value="End Session" class="text-sm font-medium text-gray-700 mb-1" />
                <div class="flex space-x-4 mt-1">
                  <label class="inline-flex items-center">
                    <input
                      type="radio"
                      v-model="form.end_half_session"
                      value="morning"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Morning</span>
                  </label>
                  <label class="inline-flex items-center">
                    <input
                      type="radio"
                      v-model="form.end_half_session"
                      value="afternoon"
                      class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300"
                    />
                    <span class="ml-2 text-sm text-gray-700">Afternoon</span>
                  </label>
                </div>
                <InputError :message="form.errors.end_half_session" />
              </div>
            </template>

            <!-- Reason -->
            <div>
              <InputLabel for="reason" value="Reason" class="text-sm font-medium text-gray-700 mb-1" />
              <textarea
                id="reason"
                v-model="form.reason"
                rows="3"
                required
                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
                placeholder="Brief explanation for your leave request"
              ></textarea>
              <InputError :message="form.errors.reason" />
            </div>

            <!-- Submit Button -->
            <div class="pt-2">
              <PrimaryButton
                :disabled="form.processing"
                class="w-full justify-center py-2 px-4 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
              >
                {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
              </PrimaryButton>
            </div>
          </div>
        </form>
      </div>

      <!-- Leave Requests Table -->
      <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
          <h3 class="text-lg font-semibold text-gray-900">
            {{ canManage ? 'Employee Leave Requests' : 'Your Leave History' }}
          </h3>
        </div>

        <div v-if="props.leaveRequests.length === 0" class="p-8 text-center">
          <div class="text-gray-400 text-lg">No leave requests found</div>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
              <tr>
                <th v-if="canManage" scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Employee
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Dates
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Type
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Duration
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Reason
                </th>
                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Status
                </th>
                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                  Actions
                </th>
              </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
              <tr v-for="request in props.leaveRequests" :key="request.id" class="hover:bg-gray-50">
                <td v-if="canManage" class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm font-medium text-gray-900">{{ request.user?.name }}</div>
                  <div class="text-sm text-gray-500">{{ request.user?.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <div class="text-sm text-gray-900">
                    {{ new Date(request.start_date).toLocaleDateString() }}
                    <span v-if="request.start_date !== request.end_date">
                      - {{ new Date(request.end_date).toLocaleDateString() }}
                    </span>
                  </div>
                  <div v-if="request.day_type === 'half'" class="text-xs text-gray-500">
                    {{ request.start_half_session || 'full' }} session
                    <span v-if="request.start_date !== request.end_date">
                      to {{ request.end_half_session || 'full' }} session
                    </span>
                  </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">
                  {{ request.leave_type }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                  {{ formatLeaveDays(request.leave_days) }} day{{ request.leave_days !== 1 ? 's' : '' }}
                </td>
                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                  {{ request.reason }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusConfig[request.status].class" class="px-2 py-1 rounded-full text-xs font-medium inline-flex items-center">
                    {{ statusConfig[request.status].icon }} {{ request.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                  <div v-if="canManage && request.status === 'pending'" class="space-x-2">
                    <button @click="updateStatus(request, 'approved')" class="text-green-600 hover:text-green-900">
                      Approve
                    </button>
                    <button @click="updateStatus(request, 'rejected')" class="text-red-600 hover:text-red-900">
                      Reject
                    </button>
                  </div>
                  <button
                    v-else-if="!canManage && request.status === 'pending'"
                    @click="cancelLeave(request)"
                    class="text-red-600 hover:text-red-900"
                  >
                    Cancel
                  </button>
                  <span v-else class="text-gray-400">-</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
.fc {
  @apply text-gray-800;
}

.fc .fc-toolbar.fc-header-toolbar {
  margin-bottom: 0.5em;
}

.fc .fc-toolbar-title {
  font-size: 1.1em;
  @apply font-semibold;
}

.fc .fc-button {
  @apply bg-white border-gray-300 text-gray-700 hover:bg-gray-50;
}

.fc .fc-button-primary:not(:disabled).fc-button-active {
  @apply bg-blue-600 text-white;
}

.fc .fc-daygrid-day-frame {
  @apply min-h-[2em];
}

.fc .fc-daygrid-day-number {
  @apply p-1 text-gray-800 hover:text-blue-600;
}

.fc .fc-daygrid-day.fc-day-today {
  @apply bg-blue-50;
}

.fc .fc-daygrid-day-top {
  @apply flex-col items-start;
}

.fc .fc-col-header-cell {
  @apply bg-gray-50;
}

.fc .fc-col-header-cell-cushion {
  @apply text-gray-700 text-sm font-medium py-2;
}

.fc .fc-daygrid-day-events {
  @apply mt-1;
}

.fc .fc-event {
  @apply text-white text-xs rounded p-1 mb-1;
}

.fc .fc-daygrid-day.fc-day-disabled {
  @apply bg-gray-50;
}

.fc .fc-daygrid-day.fc-day-disabled .fc-daygrid-day-number {
  @apply text-gray-400;
}

select option {
  position: relative;
}

</style>
