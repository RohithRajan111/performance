<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue'
import InputLabel from '@/Components/InputLabel.vue'
import PrimaryButton from '@/Components/PrimaryButton.vue'
import { Head, useForm, router, usePage } from '@inertiajs/vue3'
import { ref, watch, computed } from 'vue'

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
  BriefcaseIcon,
  SparklesIcon,
} from '@heroicons/vue/24/outline'

const props = defineProps({
  leaveRequests: Array,
  canManage: Boolean,
  highlightedDates: Array,
  leave_balances: Array,
})

const page = usePage();

const leaveColors = {
  pending: '#fbbf24',
  annual: '#3b82f6',
  approved: '#22c55e',
  sick: '#14b8a6',
  personal: '#f59e0b',
  rejected: '#b91c1c',
  maternity: '#ec4899',
  paternity: '#6366f1',
  emergency: '#f97316',
  holiday: 'bg-purple-500',
}

const today = new Date();
today.setHours(0, 0, 0, 0);

const isLeaveModalVisible = ref(false);

const form = useForm({
  start_date: '',
  end_date: '',
  start_half_session: '',
  end_half_session: '',
  reason: '',
  leave_type: 'annual',
})

function toISODateOnly(date) {
  if (!date) return '';
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');
  return `${year}-${month}-${day}`;
}

const calendarEvents = computed(() => {
    return (props.highlightedDates || []).map(ev => ({
      display: 'background',
      start: ev.start,
      end: ev.end ? toISODateOnly(new Date(new Date(ev.end + 'T00:00:00').getTime() + 24 * 60 * 60 * 1000)) : ev.start,
      color: leaveColors[ev.color_category] || '#9ca3af',
    }));
});

const upcomingEvents = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return (props.highlightedDates || [])
        .filter(event => new Date(event.start + 'T00:00:00') >= today)
        .sort((a, b) => new Date(a.start) - new Date(b.start))
        .slice(0, 4);
});

const handleDateClick = (info) => {
    const clicked = new Date(info.dateStr + 'T00:00:00');
    if (clicked < today) {
        alert('Please select a date that is today or in the future.');
        return;
    }
    form.reset();
    form.start_date = info.dateStr;
    form.end_date = info.dateStr;
    isLeaveModalVisible.value = true;
};

const calendarOptions = ref({
  plugins: [dayGridPlugin, interactionPlugin],
  initialView: 'dayGridMonth',
  height: 'auto',
  events: calendarEvents.value,
  dateClick: handleDateClick,
  headerToolbar: {
    left: 'title',
    center: '',
    right: 'prev,next'
  },
})

watch(calendarEvents, (newEvents) => {
  calendarOptions.value.events = newEvents;
});

// ⭐️ THIS IS THE CORRECTED FUNCTION ⭐️
const computedLeaveDays = computed(() => {
    const start = form.start_date ? new Date(form.start_date + 'T00:00:00') : null;
    const end = form.end_date ? new Date(form.end_date + 'T00:00:00') : null;
    if (!start || !end || start > end) return 0;

    const sHalf = form.start_half_session;
    const eHalf = form.end_half_session;
    const isSingleDay = start.getTime() === end.getTime();

    if (isSingleDay) {
        // Case 1: Full Day (Start of Day -> End of Day)
        if (!sHalf && !eHalf) {
            return 1.0;
        }
        // Case 2: First Half Day (Start of Day -> Morning)
        else if (!sHalf && eHalf === 'morning') {
            return 0.5;
        }
        // Case 3: Second Half Day (Afternoon -> End of Day)
        else if (sHalf === 'afternoon' && !eHalf) {
            return 0.5;
        }
        // Case 4: Invalid selection on the same day (Afternoon to Morning)
        else if (sHalf === 'afternoon' && eHalf === 'morning') {
            return 0; // This is an invalid duration
        }
        // Fallback for any other unexpected combination
        else {
            return 1.0;
        }
    } 
    else { // Multi-day logic
        const firstDayValue = (sHalf === 'afternoon') ? 0.5 : 1.0;
        const lastDayValue = (eHalf === 'morning') ? 0.5 : 1.0;
        const diffInMs = end.getTime() - start.getTime();
        const diffInDays = diffInMs / (1000 * 60 * 60 * 24);
        const daysInBetween = Math.max(0, diffInDays - 1);
        return firstDayValue + lastDayValue + daysInBetween;
    }
});


const closeLeaveModal = () => {
    isLeaveModalVisible.value = false;
};

const submitLeaveFromModal = () => {
  form.post(route('leave.store'), {
    preserveScroll: true,
    onSuccess: () => {
      closeLeaveModal();
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

    <!-- "Book Time Off" Modal -->
    <Modal :show="isLeaveModalVisible" @close="closeLeaveModal" max-width="lg">
        <div class="p-6 font-sans">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-bold text-gray-800">Book time off</h2>
                <button @click="closeLeaveModal" class="p-1 rounded-full text-gray-400 hover:bg-gray-200 hover:text-gray-600 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                </button>
            </div>

            <form @submit.prevent="submitLeaveFromModal" class="mt-6 space-y-6">
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <InputLabel value="Who for" />
                        <div class="mt-1 p-2 bg-gray-100 border border-gray-200 rounded-md text-sm font-medium text-gray-700">
                            {{ page.props.auth.user.name }}
                        </div>
                    </div>
                    <div>
                        <InputLabel for="modal_leave_type" value="Leave type" />
                        <select id="modal_leave_type" v-model="form.leave_type" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="annual">Annual Leave</option>
                            <option value="sick">Sick Leave</option>
                            <option value="personal">Personal Leave</option>
                            <option value="emergency">Emergency Leave</option>
                            <option value="maternity">Maternity Leave</option>
                            <option value="paternity">Paternity Leave</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <InputLabel for="modal_start_date" value="Starting" />
                        <input type="date" id="modal_start_date" v-model="form.start_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        <select v-model="form.start_half_session" class="mt-2 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">Start of day</option>
                            <option value="afternoon">Afternoon</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel for="modal_end_date" value="Ending" />
                        <input type="date" id="modal_end_date" v-model="form.end_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm text-sm" />
                        <select v-model="form.end_half_session" class="mt-2 block w-full border-gray-300 rounded-md shadow-sm text-sm">
                            <option value="">End of day</option>
                            <option value="morning">Afternoon</option>
                        </select>
                    </div>
                </div>

                <div>
                    <InputLabel for="modal_reason" value="Reason" is-required />
                    <textarea id="modal_reason" v-model="form.reason" rows="3" required placeholder="e.g., Family vacation, medical appointment" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                    <InputError class="mt-1" :message="form.errors.reason" />
                </div>

                <div class="border-t border-gray-200 pt-5">
                    <p v-if="computedLeaveDays > 0" class="text-center text-sm text-gray-600 mb-5">
                        This request will use <b class="text-indigo-600">{{ formatLeaveDays(computedLeaveDays) }} day{{ computedLeaveDays !== 1 ? 's' : '' }}</b> from your leave balance.
                    </p>
                    <div class="flex items-center justify-end gap-3">
                        <button type="button" @click="closeLeaveModal" class="px-5 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50">
                            Cancel
                        </button>
                        <PrimaryButton :disabled="form.processing" class="bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500 px-5 py-2">
                            {{ form.processing ? 'Submitting...' : 'Send Request' }}
                        </PrimaryButton>
                    </div>
                </div>
            </form>
        </div>
    </Modal>

    <!-- Main Page Content -->
    <div class="py-8 max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
      
      <!-- Employee View (Calendar + Info Panel) -->
      <div v-if="!canManage" class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-start">
        
        <!-- Left Column: Calendar -->
        <div class="lg:col-span-2 bg-white p-4 sm:p-6 rounded-xl shadow-sm border border-gray-200">
           <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                    <CalendarDaysIcon class="h-6 w-6 text-blue-500" />
                    Leave Calendar & Booking
                </h3>
           </div>
           <p class="text-sm text-gray-500 mb-4">Click a date on the calendar to open the request form.</p>
           <div class="border border-gray-200 rounded-lg overflow-hidden">
               <FullCalendar :options="calendarOptions" />
           </div>
        </div>

        <!-- Right Column: Info Panel -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Widget 1: Leave Balance Breakdown -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2 mb-4">
                    <BriefcaseIcon class="h-6 w-6 text-gray-500" />
                    Leave Balances
                </h3>
                <div v-if="props.leave_balances && props.leave_balances.length > 0" class="space-y-4">
                    <div v-for="balance in props.leave_balances" :key="balance.type">
                        <div class="flex justify-between items-center mb-1">
                            <span class="text-sm font-medium text-gray-700">{{ balance.type }}</span>
                            <span class="text-sm font-semibold text-gray-800">{{ balance.total - balance.used }} / {{ balance.total }} <span class="font-normal text-gray-500">days</span></span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div :class="balance.color" class="h-2 rounded-full" :style="{ width: (balance.used / balance.total) * 100 + '%' }"></div>
                        </div>
                    </div>
                </div>
                <p v-else class="text-sm text-center text-gray-500 py-4">No leave balance information available.</p>
            </div>

            <!-- Widget 2: Upcoming Time Off -->
            <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-200">
                <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2 mb-4">
                    <SparklesIcon class="h-6 w-6 text-gray-500" />
                    Upcoming Time Off
                </h3>
                <ul v-if="upcomingEvents.length > 0" class="space-y-3">
                    <li v-for="event in upcomingEvents" :key="event.start" class="flex items-start gap-3">
                        <div class="flex-shrink-0 mt-1 w-3 h-3 rounded-full" :style="{ backgroundColor: leaveColors[event.color_category] || '#9ca3af' }"></div>
                        <div>
                            <p class="text-sm font-medium text-gray-800">{{ event.title }}</p>
                            <p class="text-xs text-gray-500 font-mono">{{ event.start }}<span v-if="event.end && event.end !== event.start"> to {{ event.end }}</span></p>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-center text-gray-500 py-4">No upcoming holidays or approved leave.</p>
            </div>
        </div>
      </div>

      <!-- Leave Requests Table (for both employees and managers) -->
      <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
         <div class="p-4 sm:p-6 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900 flex items-center gap-2">
                <UserGroupIcon class="h-6 w-6 text-gray-500" />
                {{ props.canManage ? 'All Employee Leave Requests' : 'Your Leave History' }}
            </h3>
        </div>

        <div v-if="!props.leaveRequests || props.leaveRequests.length === 0" class="text-center py-12 text-gray-500">
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
                <td class="px-6 py-4 whitespace-nowrap capitalize text-gray-800">{{ request.leave_type }}</td>
                <td class="px-6 py-4 whitespace-nowrap text-center font-medium text-gray-800">{{ formatLeaveDays(request.leave_days) }}</td>
                <td class="px-6 py-4"><p class="truncate max-w-xs" :title="request.reason">{{ request.reason }}</p></td>
                <td class="px-6 py-4 whitespace-nowrap">
                  <span :class="statusConfig[request.status]?.class" class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-medium capitalize">
                    <component :is="statusConfig[request.status]?.icon" class="h-3.5 w-3.5" />
                    {{ request.status }}
                  </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right">
                    <div v-if="props.canManage && request.status === 'pending'" class="flex justify-end gap-2">
                        <button @click="updateStatus(request, 'approved')" class="p-1.5 rounded-md hover:bg-green-100 text-green-600" title="Approve"><CheckBadgeIcon class="h-5 w-5"/></button>
                        <button @click="updateStatus(request, 'rejected')" class="p-1.5 rounded-md hover:bg-red-100 text-red-600" title="Reject"><XCircleIcon class="h-5 w-5"/></button>
                    </div>
                    <div v-else-if="!props.canManage && request.status === 'pending'">
                        <button @click="cancelLeave(request)" class="p-1.5 rounded-md hover:bg-red-100 text-red-600" title="Cancel Request"><TrashIcon class="h-5 w-5" /></button>
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

<style>
/* Definitive fix for FullCalendar header layout */
.fc-toolbar.fc-header-toolbar {
  display: flex;
  justify-content: space-between;
}
.fc-toolbar.fc-header-toolbar .fc-toolbar-chunk:first-of-type {
  flex-grow: 1;
  text-align: left;
}
</style>