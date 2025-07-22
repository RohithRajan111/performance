<script setup>
import { ref, onMounted } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const showingSidebar = ref(false);
const showingNotificationDropdown = ref(false);
const unreadNotificationCount = ref(0);
const notifications = ref([]);
const loading = ref(false);

const fetchNotifications = async () => {
    try {
        const [countResponse, notificationsResponse] = await Promise.all([
            axios.get('/notifications/unread-count'),
            axios.get('/notifications/recent')
        ]);
        
        unreadNotificationCount.value = countResponse.data.count;
        notifications.value = notificationsResponse.data.data;
    } catch (error) {
        console.error('Error fetching notifications:', error);
    }
};

const markAsRead = async (notificationId) => {
    try {
        await axios.post(`/notifications/${notificationId}/read`);
        await fetchNotifications();
    } catch (error) {
        console.error('Error marking notification as read:', error);
    }
};

const markAllAsRead = async () => {
    if (loading.value) return;
    
    loading.value = true;
    try {
        await axios.post('/notifications/mark-all-read');
        await fetchNotifications();
        showingNotificationDropdown.value = false;
    } catch (error) {
        console.error('Error marking all notifications as read:', error);
    } finally {
        loading.value = false;
    }
};

const toggleNotificationDropdown = () => {
    showingNotificationDropdown.value = !showingNotificationDropdown.value;
    if (showingNotificationDropdown.value) {
        fetchNotifications();
    }
};

const closeNotificationDropdown = () => {
    showingNotificationDropdown.value = false;
};

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
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

onMounted(() => {
    fetchNotifications();
    // Poll for new notifications every 30 seconds
    setInterval(fetchNotifications, 30000);
});
</script>

<template>
  <div class="min-h-screen flex bg-gray-100">
    <!-- Sidebar -->
    <aside class="w-64 bg-white shadow-md hidden md:block">
      <div class="h-16 flex items-center justify-center border-b">
        <Link :href="route('dashboard')">
          <ApplicationLogo class="h-8 w-auto text-gray-800" />
        </Link>
      </div>

      <nav class="mt-4 px-4 space-y-1">
        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
          <span class="mr-2">🏠</span> Dashboard
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage leave applications')" :href="route('leaves.calendar')" :active="route().current('leaves.calendar')">
          <span class="mr-2">📅</span> Leave Calendar
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage roles')" :href="route('roles.index')" :active="route().current('roles.index')">
          <span class="mr-2">🛡️</span> Manage Roles
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('assign projects')" :href="route('projects.create')" :active="route().current('projects.create')">
          <span class="mr-2">📁</span> New Project
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('apply for leave')" :href="route('leave.index')" :active="route().current('leave.index')">
          <span class="mr-2">🌴</span> Apply for Leave
        </NavLink>

        <NavLink :href="route('hours.index')" :active="route().current('hours.index')">
          <span class="mr-2">⏱️</span> Working Hours
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('users.index')" :active="route().current('users.index')">
          <span class="mr-2">👥</span> Manage Employees
        </NavLink>

        <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('teams.index')" :active="route().current('teams.index')">
          <span class="mr-2">👨‍👩‍👧‍👦</span> Manage Teams
        </NavLink>

        <NavLink :href="route('company.hierarchy')" :active="route().current('company.hierarchy')">
          <span class="mr-2">🏢</span> Company Hierarchy
        </NavLink>
      </nav>
    </aside>

    <!-- Main Section -->
    <div class="flex-1 flex flex-col">
      <!-- Top Navbar -->
      <header class="bg-white shadow h-16 flex items-center justify-between px-6">
        <!-- Mobile Sidebar Trigger -->
        <div class="flex items-center">
          <button class="md:hidden flex items-center text-gray-600 hover:text-indigo-600 mr-4" @click="showingSidebar = !showingSidebar">
            <svg class="h-6 w-6" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
          </button>
          
          <div class="text-lg font-semibold text-gray-700">
            <slot name="header" />
          </div>
        </div>

        <div class="flex items-center space-x-4">
          <!-- Notifications Dropdown -->
          <div class="relative">
            <button 
              @click="toggleNotificationDropdown"
              @blur="setTimeout(() => closeNotificationDropdown(), 200)"
              class="relative flex items-center text-gray-600 hover:text-indigo-600 focus:outline-none focus:text-indigo-600 transition duration-150 ease-in-out p-2"
            >
              <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
              </svg>
              <span 
                v-if="unreadNotificationCount > 0" 
                class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-500 rounded-full"
              >
                {{ unreadNotificationCount > 99 ? '99+' : unreadNotificationCount }}
              </span>
            </button>

            <!-- Notifications Dropdown -->
            <div 
              v-show="showingNotificationDropdown"
              class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-50 border border-gray-200"
            >
              <div class="px-4 py-3 border-b border-gray-200">
                <div class="flex items-center justify-between">
                  <h3 class="text-lg font-medium text-gray-900">Notifications</h3>
                  <button 
                    v-if="unreadNotificationCount > 0"
                    @click="markAllAsRead"
                    :disabled="loading"
                    class="text-sm text-indigo-600 hover:text-indigo-800 disabled:opacity-50"
                  >
                    {{ loading ? 'Loading...' : 'Mark all as read' }}
                  </button>
                </div>
              </div>
              
              <div class="max-h-96 overflow-y-auto">
                <div v-if="notifications.length === 0" class="px-4 py-6 text-center text-gray-500">
                  <div class="text-4xl mb-2">📭</div>
                  <p>No notifications</p>
                </div>
                
                <div 
                  v-for="notification in notifications" 
                  :key="notification.id"
                  class="px-4 py-3 hover:bg-gray-50 border-b border-gray-100 cursor-pointer transition-colors duration-150"
                  :class="{ 'bg-indigo-50': !notification.read_at }"
                  @click="markAsRead(notification.id)"
                >
                  <div class="flex items-start space-x-3">
                    <div class="text-lg">
                      {{ getNotificationIcon(notification.data.type) }}
                    </div>
                    <div class="flex-1 min-w-0">
                      <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900 truncate">
                          {{ notification.data.title }}
                        </p>
                        <div class="flex items-center space-x-2">
                          <span class="text-xs text-gray-500">
                            {{ formatDate(notification.created_at) }}
                          </span>
                          <span v-if="!notification.read_at" class="inline-block w-2 h-2 bg-indigo-500 rounded-full"></span>
                        </div>
                      </div>
                      <p class="text-sm text-gray-600 mt-1">
                        {{ notification.data.message }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
              
              <div class="px-4 py-3 border-t border-gray-200 bg-gray-50">
                <Link 
                  :href="route('notifications.index')" 
                  class="block text-center text-sm text-indigo-600 hover:text-indigo-800 font-medium"
                  @click="closeNotificationDropdown"
                >
                  View all notifications
                </Link>
              </div>
            </div>
          </div>

          <!-- User Dropdown -->
          <div>
            <Dropdown align="right" width="48">
              <template #trigger>
                <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:text-gray-900 focus:outline-none">
                  {{ $page.props.auth.user.name }}
                  <svg class="ml-2 h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 20" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8l5 5 5-5" />
                  </svg>
                </button>
              </template>
              <template #content>
                <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
                <DropdownLink :href="route('notifications.index')">Notifications</DropdownLink>
                <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
              </template>
            </Dropdown>
          </div>
        </div>
      </header>

      <!-- Mobile Sidebar Overlay -->
      <div 
        v-if="showingSidebar" 
        class="fixed inset-0 z-40 md:hidden"
        @click="showingSidebar = false"
      >
        <div class="fixed inset-0 bg-black opacity-50"></div>
        <aside class="fixed left-0 top-0 bottom-0 w-64 bg-white shadow-md z-50 transform transition-transform">
          <div class="h-16 flex items-center justify-between border-b px-4">
            <Link :href="route('dashboard')">
              <ApplicationLogo class="h-8 w-auto text-gray-800" />
            </Link>
            <button @click="showingSidebar = false" class="text-gray-600 hover:text-gray-900">
              <svg class="h-6 w-6" fill="none" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>

          <nav class="mt-4 px-4 space-y-1">
            <NavLink :href="route('dashboard')" :active="route().current('dashboard')" @click="showingSidebar = false">
              <span class="mr-2">🏠</span> Dashboard
            </NavLink>

            <NavLink v-if="$page.props.auth.user.permissions.includes('manage leave applications')" :href="route('leaves.calendar')" :active="route().current('leaves.calendar')" @click="showingSidebar = false">
              <span class="mr-2">📅</span> Leave Calendar
            </NavLink>

            <NavLink v-if="$page.props.auth.user.permissions.includes('manage roles')" :href="route('roles.index')" :active="route().current('roles.index')" @click="showingSidebar = false">
              <span class="mr-2">🛡️</span> Manage Roles
            </NavLink>

            <NavLink v-if="$page.props.auth.user.permissions.includes('assign projects')" :href="route('projects.create')" :active="route().current('projects.create')" @click="showingSidebar = false">
              <span class="mr-2">📁</span> New Project
            </NavLink>

            <NavLink v-if="$page.props.auth.user.permissions.includes('apply for leave')" :href="route('leave.index')" :active="route().current('leave.index')" @click="showingSidebar = false">
              <span class="mr-2">🌴</span> Apply for Leave
            </NavLink>

            <NavLink :href="route('hours.index')" :active="route().current('hours.index')" @click="showingSidebar = false">
              <span class="mr-2">⏱️</span> Working Hours
            </NavLink>

            <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('users.index')" :active="route().current('users.index')" @click="showingSidebar = false">
              <span class="mr-2">👥</span> Manage Employees
            </NavLink>

            <NavLink v-if="$page.props.auth.user.permissions.includes('manage employees')" :href="route('teams.index')" :active="route().current('teams.index')" @click="showingSidebar = false">
              <span class="mr-2">👨‍👩‍👧‍👦</span> Manage Teams
            </NavLink>

            <NavLink :href="route('company.hierarchy')" :active="route().current('company.hierarchy')" @click="showingSidebar = false">
              <span class="mr-2">🏢</span> Company Hierarchy
            </NavLink>
          </nav>
        </aside>
      </div>

      <!-- Page Content -->
      <main class="flex-1 p-4">
        <slot />
      </main>
    </div>
  </div>
</template>
