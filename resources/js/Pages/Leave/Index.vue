<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router } from '@inertiajs/vue3'
import { ref, watch, onMounted, computed } from 'vue'

import FullCalendar from '@fullcalendar/vue3'
import dayGridPlugin from '@fullcalendar/daygrid'
import interactionPlugin from '@fullcalendar/interaction'
import {
  UserGroupIcon,
  CalendarDaysIcon,
  CheckBadgeIcon,
  XCircleIcon,
  PencilSquareIcon,
  TrashIcon,
} from '@heroicons/vue/24/outline'


const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array,
  remainingLeaveBalance: Number,
})

const leaveColors = {
  pending: '#fbbf24',
  annual: '#3b82f6',
  approved: '#22c55e',
  sick: '#14b8a6',
  personal: '#f59e0b',
  paid: '#ef4444',
  rejected: '#b91c1c',
  maternity: '#ec4899',
  paternity: '#6366f1',
  emergency: '#f97316',
  unknown: '#9ca3af',
}

const selectedDates = ref([null, null])
const today = new Date()
today.setHours(0, 0, 0, 0)

const form = useForm({
  start_date: '',
  end_date: '',
  start_half_session: '',
  end_half_session: '',
  reason: '',
  leave_type: 'annual',
})

// ⭐️ FIX #1, PART A: The timezone-safe date-to-string function.
function toISODateOnly(date) {
  if (!date) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const calendarEvents = computed(() => {
    const backgroundEvents = (props.highlightedDates || []).map(ev => ({
      display: 'background',
      start: ev.start,
      end: ev.end ? toISODateOnly(new Date(new Date(ev.end + 'T00:00:00').getTime() + 24 * 60 * 60 * 1000)) : ev.start,
      color: leaveColors[ev.color_category] || '#9ca3af',
    }));

    const [start, end] = selectedDates.value;
    const selectionEvents = [];
    if (start && end) {
        selectionEvents.push({
            display: 'background',
            start: toISODateOnly(start),
            end: toISODateOnly(new Date(end.getTime() + 24 * 60 * 60 * 1000)),
            color: '#10b981',
        });
    } else if (start) {
        selectionEvents.push({
            display: 'background',
            start: toISODateOnly(start),
            allDay: true,
            color: '#10b981',
        });
    }

    return [...backgroundEvents, ...selectionEvents];
});

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  height: 'auto',
  events: calendarEvents.value,
  dateClick: (info) => {
    // ⭐️ FIX #1, PART B: Parse the clicked date string as local time.
    const clicked = new Date(info.dateStr + 'T00:00:00');

    if (clicked < today) {
        alert('Please select a date that is today or in the future.')
        return
    }
    const [start, end] = selectedDates.value
    if (!start || end) {
        selectedDates.value = [clicked, null]
    } else {
        selectedDates.value = clicked >= start ? [start, clicked] : [clicked, null]
    }
  },
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next'
  },
})

watch(selectedDates, ([start, end]) => {
  form.start_date = start ? toISODateOnly(start) : ''
  form.end_date = end ? toISODateOnly(end) : (start ? toISODateOnly(start) : '')
}, { deep: true });

watch(calendarEvents, (newEvents) => {
  calendarOptions.value.events = newEvents;
});


const computedLeaveDays = computed(() => {
    const start = form.start_date ? new Date(form.start_date + 'T00:00:00') : null
    const end = form.end_date ? new Date(form.end_date + 'T00:00:00') : null
    if (!start || !end) return 0

    const sHalf = form.start_half_session;
    const eHalf = form.end_half_session;
    const isSingleDay = start.getTime() === end.getTime();

    if (isSingleDay) {
        const isFullDay = (sHalf === 'morning' && eHalf === 'afternoon');
        const isHalfDay = (sHalf === 'morning' && !eHalf) || (!sHalf && eHalf === 'afternoon') || (sHalf && sHalf === eHalf);
        if (isFullDay) return 1;
        if (isHalfDay) return 0.5;
        return 1;
    } else {
        let total = 0;
        const firstDayValue = (sHalf === 'afternoon') ? 0.5 : 1.0;
        const lastDayValue = (eHalf === 'morning') ? 0.5 : 1.0;
        const diffInMs = end.getTime() - start.getTime();
        const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
        const daysInBetween = Math.max(0, diffInDays - 1);
        total = firstDayValue + lastDayValue + daysInBetween;
        return total;
    }
});


const submitApplication = () => {
  if (!form.start_date) {
    alert('Please select at least a start date.')
    return
  }
  if (!form.end_date) form.end_date = form.start_date

  form.post(route('leave.store'), {
    preserveScroll: true,
    onSuccess: () => {
      form.reset()
      form.leave_type = 'annual'
      selectedDates.value = [null, null]
    },
  })
}

const formatLeaveDays = (days) => Number(days).toFixed(days % 1 === 0 ? 0 : 1);

const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: CheckBadgeIcon },
  rejected: { class: 'bg-red-100 text-red-800', icon: XCircleIcon },
  pending: { class: 'bg-amber-100 text-amber-800', icon: PencilSquareIcon },
}

const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), { status: newStatus }, { preserveScroll: true })
}

const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), { preserveScroll: true, })
  }
}
</script>

<template>
  <Head title="Leave Applications" />

  <AuthenticatedLayout>
    <template #header>
      <h2 class="font-semibold text-xl text-gray-800 leading-tight">Leave Applications</h2>
    </template>

    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
      
      <!-- SECTION: Leave Application Form (for non-managers) -->
      <div v-if="!canManage" class="grid grid-cols-1 md:grid-cols-5 gap-8 items-start">
        
        <!-- Left Column: Calendar -->
        <div class="md:col-span-3 bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
           <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                    <CalendarDaysIcon class="h-6 w-6 text-blue-500" />
                    Select Dates
                </h3>
                <div class="text-sm font-semibold px-3 py-1 rounded-full bg-blue-100 text-blue-800">
                  <span v-if="form.start_date">{{ form.start_date }} <span v-if="form.end_date !== form.start_date">to {{ form.end_date }}</span></span>
                  <span v-else>No dates selected</span>
                </div>
            </div>
          <div class="border border-gray-200 rounded-lg overflow-hidden">
            <FullCalendar :options="calendarOptions" />
          </div>
          <InputError class="mt-2" :message="form.errors.start_date || form.errors.end_date" />
        </div>

        <!-- Right Column: Form Fields -->
        <div class="md:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200 space-y-6">
            
            <!-- Stat Card: Remaining Balance -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-blue-800">Remaining Leave Balance</p>
                    <p class="text-3xl font-bold text-blue-900">{{ props.remainingLeaveBalance }} <span class="text-lg font-medium">days</span></p>
                </div>
                <CheckBadgeIcon class="h-10 w-10 text-blue-400" />
            </div>

            <!-- Step 1: Leave Type -->
            <div>
              <InputLabel for="leave_type" value="1. Select Leave Type" class="font-semibold text-gray-800" />
              <select id="leave_type" v-model="form.leave_type" required class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                <option value="annual">Annual Leave</option>
                <option value="sick">Sick Leave</option>
                <option value="personal">Personal Leave</option>
                <option value="emergency">Emergency Leave</option>
                <option value="maternity">Maternity Leave</option>
                <option value="paternity">Paternity Leave</option>
              </select>
              <InputError class="mt-1" :message="form.errors.leave_type" />
            </div>

            <!-- Step 2: Duration -->
            <div>
                <InputLabel value="2. Specify Duration (optional)" class="font-semibold text-gray-800" />
                <div class="grid grid-cols-2 gap-4 mt-2">
                    <div>
                        <InputLabel for="start_session" value="Start Session" class="text-sm" />
                        <select id="start_session" v-model="form.start_half_session" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Full Day</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="end_session" value="End Session" class="text-sm" />
                        <select id="end_session" v-model="form.end_half_session" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Full Day</option>
                            <option value="morning">Morning</option>
                            <option value="afternoon">Afternoon</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Step 3: Reason -->
            <div>
              <InputLabel for="reason" value="3. Reason for Leave" class="font-semibold text-gray-800" />
              <textarea id="reason" v-model="form.reason" rows="3" required placeholder="e.g., Family vacation" class="mt-2 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
              <InputError class="mt-1" :message="form.errors.reason" />
            </div>

            <!-- Submission Area -->
            <div class="border-t border-gray-200 pt-4">
                <div class="text-right text-sm font-medium text-gray-700 mb-2">
                    Estimated Duration: 
                    <span class="font-bold text-indigo-600">{{ computedLeaveDays }} day{{ computedLeaveDays !== 1 ? 's' : '' }}</span>
                </div>
                <PrimaryButton @click="submitApplication" :disabled="form.processing" class="w-full justify-center py-2.5">
                    {{ form.processing ? 'Submitting...' : 'Submit Leave Request' }}
                </PrimaryButton>
            </div>
        </div>
      </div>

      <!-- SECTION: Leave Requests Table -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="p-4 sm:p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                <UserGroupIcon class="h-6 w-6 text-gray-500" />
                {{ props.canManage ? 'All Employee Leave Requests' : 'Your Leave History' }}
            </h3>
        </div>

        <div v-if="props.leaveRequests.length === 0" class="text-center py-12 text-gray-500">
          <p>No leave requests found.</p>
        </div>

        <div v-else class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-50 text-left text-xs text-gray-500 uppercase tracking-wider">
              <tr>
                <th v-if="props.canManage" class="px-6 py-3 font-medium">Employee</th>
                <th class="px-6 py-3 font-medium">Dates</th>
                <th class="px-6 py-3 font-medium">Type</th>
                <th class="px-6 py-3 font-medium text-center">Duration</th>
                <th class="px-6 py-3 font-medium">Reason</th>
                <th class="px-6 py-3 font-medium">Status</th>
                <th class="px-6 py-3 font-medium text-right">Actions</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
              <tr v-for="request in props.leaveRequests" :key="request.id">
                <td v-if="props.canManage" class="px-6 py-4 whitespace-nowrap">
                  <div class="font-medium text-gray-900">{{ request.user?.name || 'N/A' }}</div>
                  <div class="text-xs text-gray-500">{{ request.user?.email }}</div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap font-mono text-gray-700">
                  {{ toISODateOnly(new Date(request.start_date)) }}
                  <span v-if="request.end_date !== request.start_date">→ {{ toISODateOnly(new Date(request.end_date)) }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-800">
                  {{ request.leave_type }}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-center font-medium text-gray-800">
                  {{ formatLeaveDays(request.leave_days) }}
                </td>
                <td class="px-6 py-4">
                  <p class="truncate max-w-xs" :title="request.reason">{{ request.reason }}</p>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusConfig[request.status]?.class" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                    <component :is="statusConfig[request.status]?.icon" class="h-3.5 w-3.5" />
                    {{ request.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div v-if="props.canManage && request.status === 'pending'" class="flex justify-end gap-2">
                        <button @click="updateStatus(request, 'approved')" class="p-1.5 rounded-md hover:bg-green-100 text-green-600">
                            <CheckBadgeIcon class="h-5 w-5"/>
                        </button>
                        <button @click="updateStatus(request, 'rejected')" class="p-1.5 rounded-md hover:bg-red-100 text-red-600">
                            <XCircleIcon class="h-5 w-5"/>
                        </button>
                    </div>
                    <div v-else-if="!props.canManage && request.status === 'pending'">
                        <button @click="cancelLeave(request)" class="p-1.5 rounded-md hover:bg-red-100 text-red-600">
                           <TrashIcon class="h-5 w-5" />
                        </button>
                    </div>
                    <span v-else class="text-xs text-gray-400">--</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<!-- ⭐️ FIX #2: Definitive CSS fix for the calendar header -->
<style>
.fc-toolbar.fc-header-toolbar {
  display: flex;
  justify-content: space-between;
}
.fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:first-of-type {
  flex-grow: 1;
  text-align: left;
}
</style>