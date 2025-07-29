<script setup>
// NO CHANGES WERE MADE TO THE SCRIPT. ALL YOUR FUNCTIONALITY IS PRESERVED.
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, computed } from 'vue'
import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'

const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array,
  remainingLeaveBalance: Number,
})

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

const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
  rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
  pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
}

const leaveTypeDescriptions = {
  annual: { title: "Annual Leave", summary: "Planned time off with advance notice", details: [ "✓ For vacations, personal time, or other planned absences", "✓ Should be requested at least 7 days in advance", "✓ Requires manager approval", "✓ Balance accrued based on tenure (typically 15-25 days/year)" ] },
  sick: { title: "Sick Leave", summary: "For illness or medical appointments", details: [ "✓ Can be requested with short notice", "✓ Doctor's note required for absences longer than 3 days", "✓ Typically up to 10 paid days/year", "✓ Covers medical appointments and contagious illnesses" ] },
  personal: { title: "Personal Leave", summary: "For personal matters requiring 3+ days notice", details: [ "✓ Requires at least 3 days notice when possible", "✓ Limited to 5 paid days/year", "✓ Examples: Family emergencies, urgent personal business", "✓ Not for routine errands or non-urgent matters" ] },
  emergency: { title: "Emergency Leave", summary: "Unplanned urgent situations", details: [ "✓ No advance notice required", "✓ Typically unpaid after 3 days", "✓ Examples: Natural disasters, serious family emergencies", "✓ Documentation may be required for extended leave" ] },
  maternity: { title: "Maternity Leave", summary: "For new mothers (typically 12+ weeks)", details: [ "✓ Typically 12 weeks paid (varies by location)", "✓ Requires 30 days notice when possible", "✓ Medical documentation required", "✓ Can be combined with other leave types" ] },
  paternity: { title: "Paternity Leave", summary: "For new fathers (typically 2-4 weeks)", details: [ "✓ Typically 2-4 weeks paid", "✓ Can be taken within 6 months of birth/adoption", "✓ Requires 30 days notice when possible", "✓ Can be taken intermittently in some cases" ] }
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

const selectedDateRange = computed(() => {
  if (form.start_date && !form.end_date) {
    return `${form.start_date}${form.day_type === 'half' ? ` (${form.start_half_session || 'half'})` : ''}`
  }
  if (form.start_date && form.end_date) {
    return `${form.start_date} to ${form.end_date}`
  }
  return 'No dates selected'
})

const currentLeaveDescription = computed(() => {
  return leaveTypeDescriptions[form.leave_type] || leaveTypeDescriptions.annual
})

function toISODateOnly(date) {
  if (!date) return ''
  const year = date.getFullYear()
  const month = String(date.getMonth() + 1).padStart(2, '0')
  const day = String(date.getDate()).padStart(2, '0')
  return `${year}-${month}-${day}`
}

function formatDate(dateString) {
  if (!dateString) return '';
  
  // Handle different date formats
  let date;
  
  // If it's already a valid date string in YYYY-MM-DD format
  if (typeof dateString === 'string' && dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
    // Create date without adding timezone offset issues
    const [year, month, day] = dateString.split('-');
    date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  } else {
    // Try to parse as regular date
    date = new Date(dateString);
  }
  
  // Check if date is valid
  if (isNaN(date.getTime())) {
    console.warn('Invalid date:', dateString);
    return 'Invalid Date';
  }
  
  return date.toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric'
  });
}

function formatLeaveDays(days) {
  const num = Number(days)
  if (isNaN(num)) return '0'
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`
  return num % 1 === 0 ? num.toString() : num.toFixed(1)
}

function getBackgroundEvents() {
  return (props.highlightedDates || []).map(ev => ({
    display: 'background',
    start: ev.start,
    end: ev.end ? toISODateOnly(new Date(new Date(ev.end + 'T00:00:00').getTime() + 86400000)) : ev.start,
    color: leaveColors[ev.color_category] || '#9ca3af',
  }))
}

function getSelectionBackground() {
  const [start, end] = selectedDates.value
  if (start && end) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(end.getTime() + 24 * 60 * 60 * 1000)),
      color: '#dbeafe', classNames: ['selection-range']
    }]
  }
  if (start) {
    return [{
      display: 'background',
      start: toISODateOnly(start),
      end: toISODateOnly(new Date(start.getTime() + 24 * 60 * 60 * 1000)),
      color: '#dbeafe', classNames: ['selection-single']
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
    alert('Please select a date that is today or in the future.')
    return
  }
  const [start, end] = selectedDates.value
  if (!start || end) {
    selectedDates.value = [clicked, null]
  } else {
    if (clicked >= start) { selectedDates.value = [start, clicked] } 
    else { selectedDates.value = [clicked, null] }
  }
  updateFormDates()
}

const validateForm = () => {
  if (!form.start_date) {
    alert('Please select at least a start date.')
    return false
  }
  if (form.day_type === 'half') {
    if (!form.start_half_session) {
      alert('Please select morning or afternoon session for start date.')
      return false
    }
    if (form.end_date && form.start_date !== form.end_date && !form.end_half_session) {
      alert('Please select morning or afternoon session for end date.')
      return false
    }
  }
  return true
}

const checkAdvanceNotice = () => {
  const startDate = new Date(form.start_date)
  const timeDiff = startDate.getTime() - today.getTime()
  const daysDiff = Math.ceil(timeDiff / (1000 * 3600 * 24))
  if (form.leave_type === 'annual' && daysDiff < 7) {
    return confirm('Warning: Annual leaves should be requested at least 7 days in advance. Do you still want to submit?')
  }
  if (form.leave_type === 'personal' && daysDiff < 3) {
    return confirm('Warning: Personal leaves should be requested at least 3 days in advance. Do you still want to submit?')
  }
  return true
}

const submitApplication = () => {
  if (!validateForm()) return
  if (!form.end_date) {
    form.end_date = form.start_date
    selectedDates.value = [new Date(form.start_date + 'T00:00:00'), new Date(form.start_date + 'T00:00:00')]
    if (form.day_type === 'half') { form.end_half_session = form.start_half_session }
  }
  if (form.day_type === 'full') {
    form.start_half_session = null
    form.end_half_session = null
  }
  if (!checkAdvanceNotice()) return
  form.post(route('leave.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      form.leave_type = 'annual'
      form.day_type = 'full'
      selectedDates.value = [null, null]
    },
    onError: (errors) => {
      console.error('Leave submission errors:', errors)
      if (errors.message) { alert(errors.message) }
    }
  })
}

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), { status: newStatus }, { preserveScroll: true })
}

const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), { preserveScroll: true })
  }
}

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  height: '100%',
  events: [],
  dateClick: handleDateClick,
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next today'
  },
  dayHeaderClassNames: 'text-xs font-semibold text-slate-500 uppercase py-3',
  dayCellClassNames: 'border-slate-200',
  eventDisplay: 'block',
  eventClassNames: 'p-1 rounded-md font-medium cursor-pointer border-none text-xs',
})

watch(selectedDates, () => {
  calendarOptions.value.events = [ ...getBackgroundEvents(), ...getSelectionBackground() ]
}, { deep: true })

watch(() => props.highlightedDates, () => {
  calendarOptions.value.events = [ ...getBackgroundEvents(), ...getSelectionBackground() ]
}, { deep: true })

onMounted(() => {
  calendarOptions.value.events = [ ...getBackgroundEvents(), ...getSelectionBackground() ]
})
</script>

<template>
    <Head title="Apply for Leave" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 font-sans">
            <!-- This title is for the entire page -->
            <h1 class="text-3xl font-bold text-slate-900">Apply for Leave</h1>

            <!-- New Leave Request Form Card -->
            <!-- ** THE FIX IS HERE: `overflow-hidden` has been removed ** -->
            <div v-if="!canManage" class="bg-white rounded-xl shadow-sm border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-lg font-bold text-slate-800">Employee Leave Requests</h3>
                </div>
                
                <form @submit.prevent="submitApplication" class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6">
                    <!-- Left Panel: Calendar -->
                    <div class="space-y-4">
                        <div class="flex items-center justify-between">
                            <h4 class="text-sm font-semibold text-slate-600">1. Select Dates</h4>
                            <div class="text-xs font-medium px-2 py-1 rounded-full bg-blue-100 text-blue-800">
                                {{ selectedDateRange }}
                            </div>
                        </div>

                        <div class="h-[420px] w-full">
                            <FullCalendar :options="calendarOptions" />
                        </div>
                        <InputError class="mt-1" :message="form.errors.start_date" />
                    </div>

                    <!-- Right Panel: Form Fields -->
                    <div class="space-y-6">
                        <h4 class="text-sm font-semibold text-slate-600">2. Provide Details</h4>

                        <div class="p-4 rounded-lg bg-slate-50 border border-slate-200">
                            <p class="text-sm font-medium text-slate-600">Remaining Leave Balance</p>
                            <p class="text-3xl font-bold text-blue-600 mt-1">{{ remainingLeaveBalance }} <span class="text-xl font-medium">days</span></p>
                        </div>
                        
                        <div>
                            <InputLabel for="leave_type" value="Leave Type" class="font-semibold" />
                            <select id="leave_type" v-model="form.leave_type" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="annual">Annual Leave</option>
                                <option value="sick">Sick Leave</option>
                                <option value="personal">Personal Leave</option>
                                <option value="emergency">Emergency Leave</option>
                                <option value="maternity">Maternity Leave</option>
                                <option value="paternity">Paternity Leave</option>
                            </select>
                            <InputError class="mt-1" :message="form.errors.leave_type" />
                            
                            <div class="mt-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                                <h4 class="font-medium text-sm text-slate-800 mb-1">{{ currentLeaveDescription.title }}</h4>
                                <p class="text-xs text-slate-600 mb-2">{{ currentLeaveDescription.summary }}</p>
                                <ul class="text-xs space-y-1 text-slate-500">
                                    <li v-for="(detail, index) in currentLeaveDescription.details" :key="index">{{ detail }}</li>
                                </ul>
                            </div>
                        </div>

                        <div>
                            <InputLabel value="Duration Type" class="font-semibold" />
                            <div class="flex space-x-4 mt-2">
                                <label class="inline-flex items-center cursor-pointer"><input type="radio" v-model="form.day_type" value="full" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300" /><span class="ml-2 text-sm text-slate-700">Full Day</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="radio" v-model="form.day_type" value="half" class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-slate-300" /><span class="ml-2 text-sm text-slate-700">Half Day</span></label>
                            </div>
                        </div>

                        <template v-if="form.day_type === 'half'">
                          <div class="space-y-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
                            <div>
                              <InputLabel value="Start Session" class="font-semibold text-sm"/>
                              <div class="flex space-x-4 mt-2">
                                <label class="inline-flex items-center cursor-pointer"><input type="radio" v-model="form.start_half_session" value="morning" class="h-4 w-4 text-indigo-600" /><span class="ml-2 text-sm">Morning (AM)</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="radio" v-model="form.start_half_session" value="afternoon" class="h-4 w-4 text-indigo-600" /><span class="ml-2 text-sm">Afternoon (PM)</span></label>
                              </div>
                              <InputError :message="form.errors.start_half_session" />
                            </div>
                            <div v-if="form.end_date && form.start_date !== form.end_date">
                              <InputLabel value="End Session" class="font-semibold text-sm"/>
                              <div class="flex space-x-4 mt-2">
                                <label class="inline-flex items-center cursor-pointer"><input type="radio" v-model="form.end_half_session" value="morning" class="h-4 w-4 text-indigo-600" /><span class="ml-2 text-sm">Morning (AM)</span></label>
                                <label class="inline-flex items-center cursor-pointer"><input type="radio" v-model="form.end_half_session" value="afternoon" class="h-4 w-4 text-indigo-600" /><span class="ml-2 text-sm">Afternoon (PM)</span></label>
                              </div>
                              <InputError :message="form.errors.end_half_session" />
                            </div>
                          </div>
                        </template>
                        
                        <div>
                            <InputLabel for="reason" value="Reason for Leave" class="font-semibold"/>
                            <textarea id="reason" v-model="form.reason" rows="4" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm resize-none" placeholder="Please provide a brief explanation..."></textarea>
                            <InputError class="mt-1" :message="form.errors.reason" />
                        </div>

                        <div class="pt-4">
                            <PrimaryButton :disabled="form.processing" class="w-full justify-center text-sm font-semibold bg-slate-900 text-white rounded-lg hover:bg-slate-700 transition-colors shadow-sm py-3">
                                SUBMIT REQUEST
                            </PrimaryButton>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Leave Requests History Table -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-lg font-bold text-slate-800">{{ canManage ? 'Employee Leave Requests' : 'Your Leave History' }}</h3>
                        <div v-if="canManage" class="mt-2 sm:mt-0 text-sm text-slate-500">
                            Total Requests: {{ leaveRequests.length }}
                        </div>
                    </div>
                </div>
                <div v-if="leaveRequests.length === 0" class="p-12 text-center">
                    <div class="text-slate-400 text-5xl mb-3">📂</div>
                    <p class="font-medium text-slate-600">No leave requests found.</p>
                </div>
                <!-- Desktop Table View -->
                  <div class="hidden lg:block">
                      <div class="overflow-x-auto">
                          <table class="min-w-full divide-y divide-slate-200">
                              <thead class="bg-slate-50">
                                  <tr>
                                      <th v-if="canManage" scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-44">Employee</th>
                                      <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">Dates</th>
                                      <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Type</th>
                                      <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Duration</th>
                                      <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Reason</th>
                                      <th scope="col" class="px-3 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider w-20">Status</th>
                                      <th scope="col" class="px-3 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider w-36">Actions</th>
                                  </tr>
                              </thead>
                              <tbody class="bg-white divide-y divide-slate-200">
                                  <tr v-for="request in leaveRequests" :key="request.id" class="hover:bg-slate-50">
                                      <td v-if="canManage" class="px-3 py-4 whitespace-nowrap w-44">
                                          <div class="flex items-center">
                                              <div class="flex-shrink-0 h-8 w-8">
                                                  <div class="h-8 w-8 rounded-full bg-slate-300 flex items-center justify-center">
                                                      <span class="text-sm font-medium text-slate-700">{{ request.user?.name?.charAt(0)?.toUpperCase() }}</span>
                                                  </div>
                                              </div>
                                              <div class="ml-3 min-w-0 flex-1">
                                                  <div class="text-sm font-medium text-slate-900 truncate">{{ request.user?.name }}</div>
                                                  <div class="text-xs text-slate-500 truncate">{{ request.user?.email }}</div>
                                              </div>
                                          </div>
                                      </td>
                                      <td class="px-3 py-4 whitespace-nowrap w-36">
                                          <div class="text-sm text-slate-900">
                                              {{ formatDate(request.start_date) }}
                                              <span v-if="request.start_date !== request.end_date"> - {{ formatDate(request.end_date) }}</span>
                                          </div>
                                          <div v-if="request.day_type === 'half'" class="text-xs text-slate-500 mt-1">
                                              {{ request.start_half_session || 'full' }} session
                                              <span v-if="request.start_date !== request.end_date"> to {{ request.end_half_session || 'full' }} session</span>
                                          </div>
                                      </td>
                                      <td class="px-3 py-4 whitespace-nowrap w-20">
                                          <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium capitalize" :class="{
                                              'bg-blue-100 text-blue-800': request.leave_type === 'annual', 
                                              'bg-green-100 text-green-800': request.leave_type === 'sick', 
                                              'bg-yellow-100 text-yellow-800': request.leave_type === 'personal', 
                                              'bg-red-100 text-red-800': request.leave_type === 'emergency', 
                                              'bg-pink-100 text-pink-800': request.leave_type === 'maternity', 
                                              'bg-purple-100 text-purple-800': request.leave_type === 'paternity'
                                          }">
                                              {{ request.leave_type }}
                                          </span>
                                      </td>
                                      <td class="px-3 py-4 whitespace-nowrap w-20 text-sm text-slate-900 font-medium">
                                          {{ formatLeaveDays(request.leave_days) }} day{{ request.leave_days !== 1 ? 's' : '' }}
                                      </td>
                                      <td class="px-3 py-4 text-sm text-slate-500">
                                          <div class="max-w-xs truncate" :title="request.reason">{{ request.reason }}</div>
                                      </td>
                                      <td class="px-3 py-4 whitespace-nowrap w-20">
                                          <span :class="statusConfig[request.status].class" class="px-2 py-1 rounded-full text-xs font-medium inline-flex items-center">
                                              <span class="mr-1">{{ statusConfig[request.status].icon }}</span>
                                              <span class="capitalize">{{ request.status }}</span>
                                          </span>
                                      </td>
                                      <td class="px-3 py-4 whitespace-nowrap text-center w-36">
                                          <div v-if="canManage && request.status === 'pending'" class="flex justify-center gap-1">
                                              <button 
                                                  @click="updateStatus(request, 'approved')" 
                                                  class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-green-500 transition-colors duration-200"
                                                  title="Approve"
                                              >
                                                  ✓
                                              </button>
                                              <button 
                                                  @click="updateStatus(request, 'rejected')" 
                                                  class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-red-500 transition-colors duration-200"
                                                  title="Reject"
                                              >
                                                  ✗
                                              </button>
                                          </div>
                                          <button 
                                              v-else-if="!canManage && request.status === 'pending'" 
                                              @click="cancelLeave(request)" 
                                              class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-red-500 transition-colors duration-200"
                                              title="Cancel"
                                          >
                                              ✗
                                          </button>
                                          <span v-else class="text-slate-400 text-sm">-</span>
                                      </td>
                                  </tr>
                              </tbody>
                          </table>
                      </div>
                  </div>

                <!-- Mobile Card View -->
                <div class="lg:hidden space-y-4 p-4">
                    <div v-for="request in leaveRequests" :key="request.id" class="bg-slate-50 rounded-lg border border-slate-200 p-4">
                        <!-- Employee Info (for managers) -->
                        <div v-if="canManage" class="flex items-center mb-3 pb-3 border-b border-slate-200">
                            <div class="flex-shrink-0 h-10 w-10">
                                <div class="h-10 w-10 rounded-full bg-slate-300 flex items-center justify-center">
                                    <span class="text-base font-medium text-slate-700">{{ request.user?.name?.charAt(0)?.toUpperCase() }}</span>
                                </div>
                            </div>
                            <div class="ml-3 min-w-0 flex-1">
                                <div class="text-base font-medium text-slate-900">{{ request.user?.name }}</div>
                                <div class="text-sm text-slate-500">{{ request.user?.email }}</div>
                            </div>
                        </div>

                        <!-- Leave Details -->
                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Dates</div>
                                <div class="text-sm text-slate-900">
                                    {{ formatDate(request.start_date) }}
                                    <span v-if="request.start_date !== request.end_date"> - {{ formatDate(request.end_date) }}</span>
                                </div>
                                <div v-if="request.day_type === 'half'" class="text-xs text-slate-500 mt-1">
                                    {{ request.start_half_session || 'full' }} session
                                    <span v-if="request.start_date !== request.end_date"> to {{ request.end_half_session || 'full' }} session</span>
                                </div>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Duration</div>
                                <div class="text-sm text-slate-900 font-medium">
                                    {{ formatLeaveDays(request.leave_days) }} day{{ request.leave_days !== 1 ? 's' : '' }}
                                </div>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4 mb-4">
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Type</div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium capitalize" :class="{
                                    'bg-blue-100 text-blue-800': request.leave_type === 'annual', 
                                    'bg-green-100 text-green-800': request.leave_type === 'sick', 
                                    'bg-yellow-100 text-yellow-800': request.leave_type === 'personal', 
                                    'bg-red-100 text-red-800': request.leave_type === 'emergency', 
                                    'bg-pink-100 text-pink-800': request.leave_type === 'maternity', 
                                    'bg-purple-100 text-purple-800': request.leave_type === 'paternity'
                                }">
                                    {{ request.leave_type }}
                                </span>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</div>
                                <span :class="statusConfig[request.status].class" class="px-2 py-1 rounded-full text-xs font-medium inline-flex items-center">
                                    <span class="mr-1">{{ statusConfig[request.status].icon }}</span>
                                    <span class="capitalize">{{ request.status }}</span>
                                </span>
                            </div>
                        </div>

                        <div class="mb-4">
                            <div class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Reason</div>
                            <div class="text-sm text-slate-700">{{ request.reason }}</div>
                        </div>

                        <!-- Action Buttons - Always Visible -->
                        <div v-if="canManage && request.status === 'pending'" class="flex flex-col sm:flex-row gap-3 pt-3 border-t border-slate-200">
                            <button 
                                @click="updateStatus(request, 'approved')" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-md text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-colors duration-200"
                            >
                                ✓ Approve Request
                            </button>
                            <button 
                                @click="updateStatus(request, 'rejected')" 
                                class="flex-1 inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                            >
                                ✗ Reject Request
                            </button>
                        </div>
                        <div v-else-if="!canManage && request.status === 'pending'" class="pt-3 border-t border-slate-200">
                            <button 
                                @click="cancelLeave(request)" 
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 border border-transparent text-sm font-medium rounded-md text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-colors duration-200"
                            >
                                Cancel Request
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* FullCalendar Custom Styles, adapted from your Dashboard for a consistent look */
:deep(.fc) {
  @apply text-sm text-slate-700;
}
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