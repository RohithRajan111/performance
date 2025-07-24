<script setup>
import { reactive, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { eachDayOfInterval, format, isWithinInterval, parseISO, startOfMonth, endOfMonth, isWeekend, isPast } from 'date-fns'; // Added isPast
import { pickBy } from 'lodash-es';
import flatPickr from 'vue-flatpickr-component';
import 'flatpickr/dist/themes/light.css';

// --- Props from the controller ---
const props = defineProps({
  usersWithLeaves: Array,
  teams: Array,
  filters: Object,
  dateRange: Object,
});

// --- Reactive state for our filter form ---
const filterForm = reactive({
  search: props.filters.search ?? '',
  team_id: props.filters.team_id ?? '',
  start_date: props.dateRange.start,
  end_date: props.dateRange.end,
  absent_only: !!props.filters.absent_only,
});

// --- Configuration for the date picker component ---
const singleDateConfig = {
  dateFormat: 'Y-m-d',
  altInput: true,
  altFormat: 'M j, Y',
};

// --- Main filter function ---
const applyFilters = () => {
  if (!filterForm.start_date || !filterForm.end_date) {
    alert('Please select both start and end dates');
    return;
  }
  const params = pickBy({
    search: filterForm.search,
    team_id: filterForm.team_id,
    absent_only: filterForm.absent_only ? 1 : undefined,
    start_date: filterForm.start_date,
    end_date: filterForm.end_date,
  });
  router.get(route('leaves.calendar'), params, { preserveState: true, replace: true });
};

// --- Quick Action Button Click Handlers ---
const showToday = () => {
  const today = format(new Date(), 'yyyy-MM-dd');
  filterForm.start_date = today;
  filterForm.end_date = today;
  filterForm.absent_only = true;
  applyFilters();
};

const setCurrentMonth = () => {
  const today = new Date();
  filterForm.start_date = format(startOfMonth(today), 'yyyy-MM-dd');
  filterForm.end_date = format(endOfMonth(today), 'yyyy-MM-dd');
  filterForm.absent_only = false;
  applyFilters();
};

const setCurrentWeek = () => {
  const today = new Date();
  const dayOfWeek = today.getDay() === 0 ? 7 : today.getDay(); // Monday=1, Sunday=7
  const startOfWeek = new Date(today);
  startOfWeek.setDate(today.getDate() - dayOfWeek + 1);
  const endOfWeek = new Date(startOfWeek);
  endOfWeek.setDate(startOfWeek.getDate() + 6);

  filterForm.start_date = format(startOfWeek, 'yyyy-MM-dd');
  filterForm.end_date = format(endOfWeek, 'yyyy-MM-dd');
  filterForm.absent_only = false;
  applyFilters();
};

const resetFilters = () => {
  filterForm.search = '';
  filterForm.team_id = '';
  filterForm.absent_only = false;
  setCurrentMonth();
};

// --- Date change handlers ---
const handleStartDateChange = () => {
  if (filterForm.end_date && filterForm.start_date > filterForm.end_date) {
    filterForm.end_date = filterForm.start_date;
  }
};

const handleEndDateChange = () => {
  if (filterForm.start_date && filterForm.end_date < filterForm.start_date) {
    filterForm.start_date = filterForm.end_date;
  }
};

// --- Computed Properties & Helpers ---
const calendarDays = computed(() => {
  if (!props.dateRange.start || !props.dateRange.end) return [];
  try {
    const start = parseISO(props.dateRange.start);
    const end = parseISO(props.dateRange.end);
    return eachDayOfInterval({ start, end }).map(day => ({
      date: format(day, 'yyyy-MM-dd'),
      dayOfMonth: format(day, 'd'),
      isWeekend: isWeekend(day),
      isPast: isPast(day) && format(day, 'yyyy-MM-dd') !== format(new Date(), 'yyyy-MM-dd')
    }));
  } catch (error) {
    console.error('Error generating calendar days:', error);
    return [];
  }
});

const isLeaveDay = (user, date) => {
  return user.leave_applications?.some(leave =>
    isWithinInterval(parseISO(date), {
      start: parseISO(leave.start_date),
      end: parseISO(leave.end_date),
    })
  );
};

const getLeaveReason = (user, date) => {
  const leave = user.leave_applications?.find(l =>
    isWithinInterval(parseISO(date), {
      start: parseISO(l.start_date),
      end: parseISO(l.end_date),
    })
  );
  return leave?.reason || 'On Leave';
};
</script>

<template>
  <Head title="Leave Calendar" />
  <AuthenticatedLayout>
    <div class="p-4 sm:p-6 lg:p-8 font-sans">
      <div class="max-w-full mx-auto space-y-6">

        <!-- Page Header -->
        <div class="flex items-center justify-between">
            <h1 class="text-3xl font-bold text-slate-900">Leave Calendar</h1>
        </div>

        <!-- Filter Card -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-200">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-4 items-end">
                <!-- Employee Search -->
                <div>
                    <label for="search" class="block text-sm font-medium text-slate-700">Employee</label>
                    <input v-model="filterForm.search" @keyup.enter="applyFilters" id="search" type="text" placeholder="Search name…"
                           class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Department Select -->
                <div>
                    <label for="team" class="block text-sm font-medium text-slate-700">Department</label>
                    <select v-model="filterForm.team_id" id="team"
                            class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                        <option value="">All Departments</option>
                        <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
                    </select>
                </div>
                <!-- Start Date -->
                <div>
                    <label for="start-date" class="block text-sm font-medium text-slate-700">Start Date</label>
                    <flat-pickr v-model="filterForm.start_date" @on-change="handleStartDateChange" id="start-date" :config="singleDateConfig"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- End Date -->
                <div>
                    <label for="end-date" class="block text-sm font-medium text-slate-700">End Date</label>
                    <flat-pickr v-model="filterForm.end_date" @on-change="handleEndDateChange" id="end-date" :config="singleDateConfig"
                                class="mt-1 block w-full rounded-md border-slate-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" />
                </div>
                <!-- Filter & Quick Action Buttons -->
                <div class="xl:col-span-2 grid grid-cols-1 gap-3">
                     <div class="flex items-center space-x-3">
                        <input v-model="filterForm.absent_only" id="absent-toggle" type="checkbox"
                               class="h-4 w-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500" />
                        <label for="absent-toggle" class="text-sm font-medium text-slate-700">
                          Show absent only
                        </label>
                     </div>
                     <div class="flex items-center justify-between space-x-2">
                        <button @click="showToday" class="flex-1 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-md py-2 px-2 transition-colors">Today</button>
                        <button @click="setCurrentWeek" class="flex-1 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-md py-2 px-2 transition-colors">This Week</button>
                        <button @click="setCurrentMonth" class="flex-1 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-md py-2 px-2 transition-colors">This Month</button>
                        <button @click="applyFilters"
                            class="flex-1 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                            Apply
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Calendar Table Card -->
        <div class="bg-white rounded-xl shadow-sm border border-slate-200">
            <header class="p-4 sm:px-6 border-b border-slate-200 flex justify-between items-center">
                 <p class="text-sm font-medium text-slate-700">
                    Showing period:
                    <strong class="text-slate-900 ml-1">
                      <template v-if="dateRange.start && dateRange.start === dateRange.end">{{ format(parseISO(dateRange.start), 'MMMM d, yyyy') }}</template>
                      <template v-else-if="dateRange.start && dateRange.end">{{ format(parseISO(dateRange.start), 'MMM d') }} – {{ format(parseISO(dateRange.end), 'MMM d, yyyy') }}</template>
                    </strong>
                </p>
                <button @click="resetFilters" class="text-sm text-blue-600 hover:underline font-medium">Reset Filters</button>
            </header>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-50">
                        <tr>
                            <th scope="col" class="sticky left-0 bg-slate-50 z-10 px-4 py-2 text-sm font-semibold text-left text-slate-600 w-48 border-r border-slate-200">Employee</th>
                            <th v-for="day in calendarDays" :key="day.date"
                                class="px-2 py-2 text-center text-xs font-medium text-slate-500 border-l border-slate-200"
                                :class="{ 'text-slate-400': day.isWeekend }">
                                {{ day.dayOfMonth }}
                            </th>
                        </tr>
                    </thead>
                    <tbody v-if="usersWithLeaves.length > 0" class="bg-white">
                        <tr v-for="user in usersWithLeaves" :key="user.id" class="border-t border-slate-200">
                            <td class="sticky left-0 bg-white group-hover:bg-slate-50 z-10 px-4 py-2 text-sm font-medium text-slate-800 border-r border-slate-200 w-48 transition-colors">{{ user.name }}</td>
                            
                            <!-- CORRECTED TD with all status indicators -->
                            <td v-for="day in calendarDays"
                                :key="user.id + day.date"
                                class="text-center text-xs border-l border-slate-200 h-10 w-10"
                                :class="{
                                  'bg-red-100': isLeaveDay(user, day.date),
                                  'bg-slate-50': day.isWeekend && !isLeaveDay(user, day.date),
                                }"
                                :title="isLeaveDay(user, day.date)
                                          ? `${user.name} - ${getLeaveReason(user, day.date)}`
                                          : (day.isWeekend ? 'Weekend' : 'Working Day')"
                            >
                                <!-- Indicator 1: Leave Day -->
                                <span v-if="isLeaveDay(user, day.date)" class="text-red-700 font-bold">L</span>
                                <!-- Indicator 2: Weekend Day -->
                                <span v-else-if="day.isWeekend" class="text-slate-400">·</span>
                                <!-- Indicator 3: Past Working Day (Present) -->
                                <span v-else-if="day.isPast" class="text-green-500">✓</span>
                                <!-- Indicator 4: Future Working Day (Blank) -->
                                <span v-else></span>
                            </td>
                        </tr>
                    </tbody>
                    <tbody v-else>
                        <tr>
                            <td :colspan="calendarDays.length + 1" class="text-center py-12 text-slate-500 border-t border-slate-200">
                                <p class="font-semibold text-lg">No Results Found</p>
                                <p class="mt-1 text-sm">No employees match your current filter criteria.</p>
                                <button @click="resetFilters" class="mt-4 inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-slate-700">
                                  Reset All Filters
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>

<style>
/* This global style targets the flatpickr calendar dropdown to match our theme */
.flatpickr-calendar.light {
    background-color: white;
    border-radius: 0.75rem; /* rounded-xl */
    border-color: #e2e8f0; /* slate-200 */
    box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
}
.flatpickr-day.selected {
    background: #0f172a; /* slate-900 */
    border-color: #0f172a;
}
.flatpickr-day.today {
    border-color: #3b82f6; /* blue-500 */
}
/* Ensure sticky column has a white background on hover, not transparent */
tr:hover .sticky {
  background-color: #f8fafc; /* bg-slate-50 on hover */
}
</style>