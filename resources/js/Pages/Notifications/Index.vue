<!-- resources/js/Pages/Notifications/Index.vue -->

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

defineProps({
    notifications: Object,
});

const markAsRead = async (notificationId) => {
    try {
        await axios.post(`/notifications/${notificationId}/read`);
        window.location.reload();
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    try {
        await axios.post('/notifications/mark-all-read');
        window.location.reload();
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    }
};

const getNotificationIcon = (type) => {
    switch (type) {
        case 'leave_request':
            return '📝';
        case 'leave_approved':
            return '✅';
        case 'leave_rejected':
            return '❌';
        default:
            return '📢';
    }
};

const getNotificationColor = (type) => {
    switch (type) {
        case 'leave_request':
            return 'border-blue-200 bg-blue-50';
        case 'leave_approved':
            return 'border-green-200 bg-green-50';
        case 'leave_rejected':
            return 'border-red-200 bg-red-50';
        default:
            return 'border-gray-200 bg-gray-50';
    }
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};
</script>

<template>
    <Head title="Notifications" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    Notifications
                </h2>
                <button 
                    v-if="notifications.data.some(n => !n.read_at)"
                    @click="markAllAsRead"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-md hover:bg-indigo-700 transition-colors"
                >
                    Mark All as Read
                </button>
            </div>
        </template>

        <div class="py-6">
            <div class="max-w-4xl mx-auto">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div v-if="notifications.data.length === 0" class="text-center py-12">
                            <div class="text-6xl mb-4">📭</div>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No notifications</h3>
                            <p class="text-gray-500">You're all caught up!</p>
                        </div>

                        <div v-else class="space-y-4">
                            <div 
                                v-for="notification in notifications.data" 
                                :key="notification.id"
                                class="border rounded-lg p-4 transition-all duration-200 hover:shadow-md"
                                :class="[
                                    notification.read_at ? 'border-gray-200 bg-white' : getNotificationColor(notification.data.type)
                                ]"
                            >
                                <div class="flex items-start space-x-4">
                                    <div class="text-2xl flex-shrink-0">
                                        {{ getNotificationIcon(notification.data.type) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <h4 class="text-lg font-medium text-gray-900">
                                                    {{ notification.data.title }}
                                                    <span v-if="!notification.read_at" class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                        New
                                                    </span>
                                                </h4>
                                                <p class="text-gray-700 mt-1">
                                                    {{ notification.data.message }}
                                                </p>
                                            </div>
                                            <div class="flex-shrink-0 ml-4">
                                                <span class="text-sm text-gray-500">
                                                    {{ formatDate(notification.created_at) }}
                                                </span>
                                            </div>
                                        </div>
                                        
                                        <!-- Additional details for leave notifications -->
                                        <div v-if="notification.data.start_date && notification.data.end_date" class="mt-2 text-sm text-gray-600">
                                            <span class="font-medium">Period:</span>
                                            {{ new Date(notification.data.start_date).toLocaleDateString() }} - 
                                            {{ new Date(notification.data.end_date).toLocaleDateString() }}
                                        </div>
                                        
                                        <div class="mt-3 flex items-center space-x-4">
                                            <button 
                                                v-if="!notification.read_at"
                                                @click="markAsRead(notification.id)"
                                                class="text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                                            >
                                                Mark as read
                                            </button>
                                            <Link 
                                                v-if="notification.data.url"
                                                :href="notification.data.url"
                                                class="text-sm text-gray-600 hover:text-gray-800"
                                            >
                                                View Details →
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Pagination -->
                        <div v-if="notifications.links && notifications.data.length > 0" class="mt-8">
                            <nav class="flex items-center justify-between border-t border-gray-200 pt-6">
                                <div class="flex-1 flex justify-between sm:hidden">
                                    <Link 
                                        v-if="notifications.prev_page_url"
                                        :href="notifications.prev_page_url"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Previous
                                    </Link>
                                    <Link 
                                        v-if="notifications.next_page_url"
                                        :href="notifications.next_page_url"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50"
                                    >
                                        Next
                                    </Link>
                                </div>
                                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                    <div>
                                        <p class="text-sm text-gray-700">
                                            Showing {{ notifications.from }} to {{ notifications.to }} of {{ notifications.total }} results
                                        </p>
                                    </div>
                                    <div>
                                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                                            <template v-for="link in notifications.links" :key="link.label">
                                                <Link 
                                                    v-if="link.url"
                                                    :href="link.url"
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors"
                                                    :class="{ 'bg-indigo-50 border-indigo-500 text-indigo-600': link.active }"
                                                    v-html="link.label"
                                                />
                                                <span 
                                                    v-else
                                                    class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400"
                                                    v-html="link.label"
                                                />
                                            </template>
                                        </nav>
                                    </div>
                                </div>
                            </nav>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
