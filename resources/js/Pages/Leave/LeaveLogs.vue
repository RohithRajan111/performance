<script setup>
// --- IMPORTS ---
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue'
import { Head, router, Link } from '@inertiajs/vue3' // Import the Link component for pagination

// --- PROPS ---
// ✅ FIX: The prop now correctly expects a paginator Object from Laravel.
const props = defineProps({
  leaveRequests: Object,
  canManage: Boolean,
});

// --- CONFIGURATION & HELPERS (No changes needed here) ---
const statusConfig = {
  approved: { class: 'bg-green-100 text-green-800', icon: '✅' },
  rejected: { class: 'bg-red-100 text-red-800', icon: '❌' },
  pending: { class: 'bg-yellow-100 text-yellow-800', icon: '⏳' },
};
function formatDate(dateString) {
  if (!dateString) return '';
  const [year, month, day] = dateString.split('-');
  const date = new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  if (isNaN(date.getTime())) return 'Invalid Date';
  return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
}
function formatLeaveDays(days) {
  const num = Number(days);
  if (isNaN(num)) return '0';
  if (num % 1 === 0.5) return `${Math.floor(num)}.5`;
  return num % 1 === 0 ? num.toString() : num.toFixed(1);
}

// --- ACTION HANDLERS (No changes needed here) ---
const updateStatus = (request, newStatus) => {
  router.patch(route('leave.update', { leave_application: request.id }), { status: newStatus }, { preserveScroll: true });
}
const cancelLeave = (request) => {
  if (confirm('Are you sure you want to cancel this leave request?')) {
    router.delete(route('leave.cancel', { leave_application: request.id }), { preserveScroll: true });
  }
}
</script>

<template>
    <Head title="Leave Logs" />

    <AuthenticatedLayout>
        <div class="p-4 sm:p-6 lg:p-8 space-y-6 font-sans">
            <!-- Page Header -->
            <h1 class="text-3xl font-bold text-slate-900">Leave Application Logs</h1>

            <!-- Leave Requests History Table Card -->
            <div class="bg-white rounded-xl shadow-sm border border-slate-200">
                <div class="p-6 border-b border-slate-200">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                        <h3 class="text-lg font-bold text-slate-800">{{ canManage ? 'All Employee Leave Requests' : 'Your Leave History' }}</h3>
                        <!-- ✅ FIX: Access the 'total' count from the paginator object -->
                        <div v-if="canManage" class="mt-2 sm:mt-0 text-sm text-slate-500">
                            Total Requests: {{ leaveRequests.total }}
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <!-- ✅ FIX: Check the length of the 'data' array inside the object -->
                <div v-if="leaveRequests.data.length === 0" class="p-12 text-center">
                    <div class="text-slate-400 text-5xl mb-3">📂</div>
                    <p class="font-medium text-slate-600">No leave requests found.</p>
                </div>

                <div v-else>
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
                                    <!-- ✅ FIX: Loop over `leaveRequests.data` to get the array of items -->
                                    <tr v-for="request in leaveRequests.data" :key="request.id" class="hover:bg-slate-50">
                                        <td v-if="canManage" class="px-3 py-4 whitespace-nowrap w-44">
                                            <div class="flex items-center">
                                                <div class="flex-shrink-0 h-8 w-8"><div class="h-8 w-8 rounded-full bg-slate-300 flex items-center justify-center"><span class="text-sm font-medium text-slate-700">{{ request.user?.name?.charAt(0)?.toUpperCase() }}</span></div></div>
                                                <div class="ml-3 min-w-0 flex-1"><div class="text-sm font-medium text-slate-900 truncate">{{ request.user?.name }}</div><div class="text-xs text-slate-500 truncate">{{ request.user?.email }}</div></div>
                                            </div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap w-36">
                                            <div class="text-sm text-slate-900">{{ formatDate(request.start_date) }}<span v-if="request.start_date !== request.end_date"> - {{ formatDate(request.end_date) }}</span></div>
                                            <div v-if="request.day_type === 'half'" class="text-xs text-slate-500 mt-1">{{ request.start_half_session || 'full' }} session<span v-if="request.start_date !== request.end_date"> to {{ request.end_half_session || 'full' }} session</span></div>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap w-20">
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium capitalize" :class="{'bg-blue-100 text-blue-800': request.leave_type === 'annual', 'bg-green-100 text-green-800': request.leave_type === 'sick', 'bg-yellow-100 text-yellow-800': request.leave_type === 'personal', 'bg-red-100 text-red-800': request.leave_type === 'emergency', 'bg-pink-100 text-pink-800': request.leave_type === 'maternity', 'bg-purple-100 text-purple-800': request.leave_type === 'paternity'}">{{ request.leave_type }}</span>
                                        </td>
                                        <td class="px-3 py-4 whitespace-nowrap w-20 text-sm text-slate-900 font-medium">{{ formatLeaveDays(request.leave_days) }} day{{ request.leave_days !== 1 ? 's' : '' }}</td>
                                        <td class="px-3 py-4 text-sm text-slate-500"><div class="max-w-xs truncate" :title="request.reason">{{ request.reason }}</div></td>
                                        <td class="px-3 py-4 whitespace-nowrap w-20"><span :class="statusConfig[request.status].class" class="px-2 py-1 rounded-full text-xs font-medium inline-flex items-center"><span class="mr-1">{{ statusConfig[request.status].icon }}</span><span class="capitalize">{{ request.status }}</span></span></td>
                                        <td class="px-3 py-4 whitespace-nowrap text-center w-36">
                                            <div v-if="canManage && request.status === 'pending'" class="flex justify-center gap-1">
                                                <button @click="updateStatus(request, 'approved')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-green-700 bg-green-100 hover:bg-green-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-green-500 transition-colors duration-200" title="Approve">✓</button>
                                                <button @click="updateStatus(request, 'rejected')" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-red-500 transition-colors duration-200" title="Reject">✗</button>
                                            </div>
                                            <button v-else-if="!canManage && request.status === 'pending'" @click="cancelLeave(request)" class="inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-red-700 bg-red-100 hover:bg-red-200 focus:outline-none focus:ring-1 focus:ring-offset-1 focus:ring-red-500 transition-colors duration-200" title="Cancel">✗</button>
                                            <span v-else class="text-slate-400 text-sm">-</span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Mobile Card View -->
                    <div class="lg:hidden space-y-4 p-4">
                        <!-- ✅ FIX: Loop over `leaveRequests.data` -->
                        <div v-for="request in leaveRequests.data" :key="request.id" class="bg-slate-50 rounded-lg border border-slate-200 p-4">
                            <!-- ... Your existing mobile card content goes here (no changes needed inside) ... -->
                        </div>
                    </div>

                    <!-- ✅ NEW: Pagination Links Section -->
                    <div v-if="leaveRequests.links.length > 3" class="p-4 border-t border-slate-200">
                        <div class="flex flex-wrap -mb-1">
                            <template v-for="(link, key) in leaveRequests.links" :key="key">
                                <div v-if="link.url === null" class="mr-1 mb-1 px-4 py-3 text-sm leading-4 text-gray-400 border rounded" v-html="link.label" />
                                <Link v-else
                                    class="mr-1 mb-1 px-4 py-3 text-sm leading-4 border rounded hover:bg-white focus:border-indigo-500 focus:text-indigo-500"
                                    :class="{ 'bg-blue-600 text-white': link.active }"
                                    :href="link.url"
                                    v-html="link.label" />
                            </template>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
