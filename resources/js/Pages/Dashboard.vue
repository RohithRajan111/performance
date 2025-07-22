<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted } from 'vue';

const props = defineProps({
    projects: {
        type: Array,
        default: () => []
    },
    myTasks: {
        type: Array,
        default: () => []
    },
    pendingLeaveRequests: {
        type: Array,
        default: () => []
    },
    stats: {
        type: Object,
        default: () => ({})
    },
});

const user = usePage().props.auth.user;

// Reactive references for animations
const animatedStats = ref({
    employee_count: 0,
    project_count: 0,
});

// Animate numbers on mount
onMounted(() => {
    setTimeout(() => {
        animatedStats.value.employee_count = props.stats.employee_count || 0;
        animatedStats.value.project_count = props.stats.project_count || 0;
    }, 100);
});

// Status handling functions
const getTaskStatusColor = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-green-50 border-green-200';
        case 'in_progress':
            return 'bg-yellow-50 border-yellow-200';
        case 'pending':
            return 'bg-blue-50 border-blue-200';
        default:
            return 'bg-gray-50 border-gray-200';
    }
};

const getStatusBadgeColor = (status) => {
    switch (status) {
        case 'completed':
            return 'bg-green-100 text-green-800';
        case 'in_progress':
            return 'bg-yellow-100 text-yellow-800';
        case 'pending':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusDisplayName = (status) => {
    switch (status) {
        case 'completed':
            return 'Completed';
        case 'in_progress':
            return 'In Progress';
        case 'pending':
            return 'Pending';
        default:
            return 'Unknown';
    }
};

// Task management functions
const canStartTask = (status) => {
    return status === 'pending';
};

const canCompleteTask = (status) => {
    return status === 'in_progress';
};

const updateTaskStatus = (task, newStatus) => {
    router.patch(route('tasks.update', { task: task.id }), {
        status: newStatus
    }, {
        preserveScroll: true,
        onSuccess: () => {
            console.log('Task updated successfully');
        },
        onError: (errors) => {
            console.error('Failed to update task:', errors);
        }
    });
};

// Computed properties
const tasksByStatus = computed(() => {
    const completed = props.myTasks.filter(task => task.status === 'completed').length;
    const inProgress = props.myTasks.filter(task => task.status === 'in_progress').length;
    const pending = props.myTasks.filter(task => task.status === 'pending').length;
    
    return { completed, inProgress, pending };
});

const completionRate = computed(() => {
    if (props.myTasks.length === 0) return 0;
    return Math.round((tasksByStatus.value.completed / props.myTasks.length) * 100);
});

const getPriorityTasks = computed(() => {
    return props.myTasks
        .filter(task => task.status !== 'completed')
        .slice(0, 3);
});

const formatDate = (date) => {
    return new Date(date).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric'
    });
};
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center space-x-4">
                <div class="w-12 h-12 bg-indigo-100 rounded-full flex items-center justify-center">
                    <span class="text-xl">🏠</span>
                </div>
                <div>
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Dashboard</h2>
                    <p class="text-sm text-gray-600">Welcome back, {{ user.name }}!</p>
                </div>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                    <!-- Employee Count Card -->
                    <div v-if="stats.employee_count !== undefined" class="bg-gradient-to-br from-blue-50 to-blue-100 p-6 rounded-xl shadow-sm border border-blue-200 transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-blue-600">Total Employees</p>
                                <p class="text-3xl font-bold text-blue-900 transition-all duration-1000">
                                    {{ Math.round(animatedStats.employee_count) }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-blue-200 rounded-full flex items-center justify-center">
                                <span class="text-2xl">👥</span>
                            </div>
                        </div>
                    </div>

                    <!-- Project Count Card -->
                    <div v-if="stats.project_count !== undefined" class="bg-gradient-to-br from-green-50 to-green-100 p-6 rounded-xl shadow-sm border border-green-200 transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-green-600">Total Projects</p>
                                <p class="text-3xl font-bold text-green-900 transition-all duration-1000">
                                    {{ Math.round(animatedStats.project_count) }}
                                </p>
                            </div>
                            <div class="w-12 h-12 bg-green-200 rounded-full flex items-center justify-center">
                                <span class="text-2xl">📁</span>
                            </div>
                        </div>
                    </div>

                    <!-- My Tasks Summary Card -->
                    <div v-if="myTasks && myTasks.length" class="bg-gradient-to-br from-purple-50 to-purple-100 p-6 rounded-xl shadow-sm border border-purple-200 transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-purple-600">My Tasks</p>
                                <p class="text-3xl font-bold text-purple-900">{{ myTasks.length }}</p>
                            </div>
                            <div class="w-12 h-12 bg-purple-200 rounded-full flex items-center justify-center">
                                <span class="text-2xl">📋</span>
                            </div>
                        </div>
                        <div class="mt-4 bg-purple-200 rounded-full h-2">
                            <div 
                                class="bg-purple-600 h-2 rounded-full transition-all duration-1000 ease-out"
                                :style="{ width: completionRate + '%' }"
                            ></div>
                        </div>
                        <p class="text-sm text-purple-600 mt-2">{{ completionRate }}% completed</p>
                    </div>

                    <!-- Pending Leave Requests Card -->
                    <div v-if="pendingLeaveRequests && pendingLeaveRequests.length" class="bg-gradient-to-br from-orange-50 to-orange-100 p-6 rounded-xl shadow-sm border border-orange-200 transform hover:scale-105 transition-all duration-200">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-orange-600">Pending Leaves</p>
                                <p class="text-3xl font-bold text-orange-900">{{ pendingLeaveRequests.length }}</p>
                            </div>
                            <div class="w-12 h-12 bg-orange-200 rounded-full flex items-center justify-center">
                                <span class="text-2xl">🌴</span>
                            </div>
                        </div>
                        <p class="text-sm text-orange-600 mt-2">Require approval</p>
                    </div>
                </div>

                <!-- Task Distribution Chart -->
                <div v-if="myTasks && myTasks.length" class="bg-white p-6 rounded-xl shadow-sm border">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">My Task Distribution</h3>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div class="text-center p-4 bg-green-50 rounded-lg">
                            <div class="text-2xl font-bold text-green-600">{{ tasksByStatus.completed }}</div>
                            <div class="text-sm text-green-600">Completed</div>
                        </div>
                        <div class="text-center p-4 bg-yellow-50 rounded-lg">
                            <div class="text-2xl font-bold text-yellow-600">{{ tasksByStatus.inProgress }}</div>
                            <div class="text-sm text-yellow-600">In Progress</div>
                        </div>
                        <div class="text-center p-4 bg-blue-50 rounded-lg">
                            <div class="text-2xl font-bold text-blue-600">{{ tasksByStatus.pending }}</div>
                            <div class="text-sm text-blue-600">Pending</div>
                        </div>
                    </div>
                </div>

                <!-- Priority Tasks Section -->
                <div v-if="getPriorityTasks.length" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <span class="mr-2">⚡</span>
                                Priority Tasks
                            </h3>
                            <span class="text-sm text-gray-500">{{ getPriorityTasks.length }} tasks</span>
                        </div>
                        <div class="space-y-3">
                            <div v-for="task in getPriorityTasks" :key="task.id" 
                                 class="p-4 rounded-lg border transition-all duration-200 hover:shadow-md"
                                 :class="getTaskStatusColor(task.status)">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2 mb-2">
                                            <h4 class="font-semibold text-gray-800">{{ task.name }}</h4>
                                            <span class="px-2 py-1 text-xs font-medium rounded-full"
                                                  :class="getStatusBadgeColor(task.status)">
                                                {{ getStatusDisplayName(task.status) }}
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <span class="font-medium">Project:</span> {{ task.project?.name || 'No Project' }}
                                        </p>
                                    </div>
                                    <div class="flex gap-2 ml-4">
                                        <button v-if="canStartTask(task.status)" 
                                                @click="updateTaskStatus(task, 'in_progress')" 
                                                class="px-3 py-1 text-xs font-semibold text-white bg-blue-500 rounded-md hover:bg-blue-600 shadow-sm transition-colors">
                                            Start
                                        </button>
                                        <button v-if="canCompleteTask(task.status)" 
                                                @click="updateTaskStatus(task, 'completed')" 
                                                class="px-3 py-1 text-xs font-semibold text-white bg-green-500 rounded-md hover:bg-green-600 shadow-sm transition-colors">
                                            Complete
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pending Leave Requests -->
                <div v-if="pendingLeaveRequests && pendingLeaveRequests.length" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <span class="mr-2">📋</span>
                                Pending Leave Requests
                            </h3>
                            <Link :href="route('leave.index')" class="text-sm font-semibold text-indigo-600 hover:text-indigo-800 transition-colors">
                                Manage All →
                            </Link>
                        </div>
                        <div class="space-y-3">
                            <div v-for="request in pendingLeaveRequests.slice(0, 5)" :key="request.id" 
                                 class="p-4 rounded-lg bg-yellow-50 border border-yellow-200 hover:bg-yellow-100 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800">{{ request.user?.name || 'Unknown User' }}</p>
                                        <p class="text-sm text-gray-600 mb-1">
                                            <span class="font-medium">Period:</span>
                                            {{ formatDate(request.start_date) }} - {{ formatDate(request.end_date) }}
                                        </p>
                                        <p class="text-sm text-gray-500">{{ request.reason || 'No reason provided' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Projects List -->
                <div v-if="projects && projects.length" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <span class="mr-2">🚀</span>
                                Active Projects
                            </h3>
                            <span class="text-sm text-gray-500">{{ projects.length }} projects</span>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div v-for="project in projects" :key="project.id" 
                                 class="p-4 bg-gray-50 rounded-lg border border-gray-200 hover:border-gray-300 hover:shadow-md transition-all duration-200">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex-1">
                                        <h4 class="font-semibold text-gray-800 mb-1">{{ project.name }}</h4>
                                        <span class="px-2 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                            {{ project.status || 'Active' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex justify-end">
                                    <Link :href="route('projects.show', project.id)" 
                                          class="px-3 py-1.5 text-sm font-medium text-white bg-indigo-600 rounded-md hover:bg-indigo-700 shadow-sm transition-colors">
                                        <span v-if="user.permissions?.includes('assign tasks')">Manage</span>
                                        <span v-else>View</span>
                                    </Link>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- All Tasks Section -->
                <div v-if="myTasks && myTasks.length > 3" class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <span class="mr-2">📝</span>
                                All My Tasks
                            </h3>
                            <span class="text-sm text-gray-500">{{ myTasks.length }} total tasks</span>
                        </div>
                        <div class="space-y-3 max-h-96 overflow-y-auto">
                            <div v-for="task in myTasks" :key="task.id" 
                                 class="p-3 rounded-lg border transition-all duration-200 hover:shadow-sm"
                                 :class="getTaskStatusColor(task.status)">
                                <div class="flex justify-between items-center">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-2">
                                            <h4 class="text-sm font-medium text-gray-800">{{ task.name }}</h4>
                                            <span class="px-1.5 py-0.5 text-xs font-medium rounded"
                                                  :class="getStatusBadgeColor(task.status)">
                                                {{ getStatusDisplayName(task.status) }}
                                            </span>
                                        </div>
                                        <p class="text-xs text-gray-500 mt-1">{{ task.project?.name || 'No Project' }}</p>
                                    </div>
                                    <div class="flex gap-1 ml-3">
                                        <button v-if="canStartTask(task.status)" 
                                                @click="updateTaskStatus(task, 'in_progress')" 
                                                class="px-2 py-1 text-xs font-medium text-white bg-blue-500 rounded hover:bg-blue-600 transition-colors">
                                            Start
                                        </button>
                                        <button v-if="canCompleteTask(task.status)" 
                                                @click="updateTaskStatus(task, 'completed')" 
                                                class="px-2 py-1 text-xs font-medium text-white bg-green-500 rounded hover:bg-green-600 transition-colors">
                                            Done
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Welcome Message -->
                <div v-if="(!pendingLeaveRequests || !pendingLeaveRequests.length) && 
                           (!projects || !projects.length) && 
                           (!myTasks || !myTasks.length)" 
                     class="bg-white overflow-hidden shadow-sm sm:rounded-xl border">
                    <div class="p-12 text-center">
                        <div class="text-6xl mb-4">👋</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Welcome, {{ user.name }}!</h3>
                        <p class="text-gray-500 mb-6">There are no active items on your dashboard right now.</p>
                        <div class="flex justify-center space-x-4">
                            <Link v-if="user.permissions?.includes('apply for leave')" 
                                  :href="route('leave.index')" 
                                  class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 transition-colors">
                                Apply for Leave
                            </Link>
                            <Link :href="route('hours.index')" 
                                  class="px-4 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                                Log Working Hours
                            </Link>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
/* Smooth transitions for all interactive elements */
.transition-all {
    transition: all 0.3s ease;
}

/* Hover effects */
.hover\:scale-105:hover {
    transform: scale(1.05);
}

/* Scrollbar styling */
.overflow-y-auto::-webkit-scrollbar {
    width: 4px;
}

.overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 2px;
}

.overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>
