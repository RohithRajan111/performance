<script setup>
import { ref, computed, onMounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import axios from 'axios';

import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';

// --- NOTIFICATION LOGIC (YOURS, UNCHANGED) ---
const showingNotificationDropdown = ref(false);
const unreadNotificationCount = ref(0);
const notifications = ref([]);
const loading = ref(false);

const fetchNotifications = async () => {
    if (!usePage().props.auth.user) return;
    try {
        const [countResponse, notificationsResponse] = await Promise.all([
            axios.get(route('notifications.unread-count')),
            axios.get(route('notifications.recent'))
        ]);
        unreadNotificationCount.value = countResponse.data.count;
        notifications.value = notificationsResponse.data.data;
    } catch (error) { console.error('Error fetching notifications:', error); }
};

const handleNotificationClick = (notification) => {
    axios.post(route('notifications.read', notification.id));
    if (notification.data.url) { router.visit(notification.data.url); }
    closeNotificationDropdown();
};

const markAllAsRead = async () => {
    if (loading.value) return;
    loading.value = true;
    try {
        await axios.post(route('notifications.mark-all-read'));
        await fetchNotifications();
        showingNotificationDropdown.value = false;
    } catch (error) { console.error('Error marking all as read:', error); }
    finally { loading.value = false; }
};

const toggleNotificationDropdown = () => {
    showingNotificationDropdown.value = !showingNotificationDropdown.value;
    if (showingNotificationDropdown.value) { fetchNotifications(); }
};

const closeNotificationDropdown = () => { showingNotificationDropdown.value = false; };

const formatDate = (dateString) => {
    return new Date(dateString).toLocaleDateString('en-US', { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
};

const getNotificationIcon = (type) => {
    switch (type) {
        case 'leave_request': return '📝';
        case 'leave_approved': return '✅';
        case 'leave_rejected': return '❌';
        default: return '📢';
    }
};

onMounted(() => {
    if (usePage().props.auth.user) {
        fetchNotifications();
        setInterval(fetchNotifications, 30000);
    }
});

// --- ORGNICE LAYOUT LOGIC ---
const page = usePage();
const user = computed(() => page.props.auth.user);
const userDesignation = computed(() => user.value?.designation || 'Employee');
const showingSidebar = ref(false);

// --- NEW SVG ICONS TO MATCH THE ORGNICE THEME ---
const icons = {
    Dashboard: `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z" /></svg>`,
    'Leave Calendar': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`,
    'Manage Roles': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 20.944A12.02 12.02 0 0012 22a12.02 12.02 0 009-1.056c.343-.344.664-.714.944-1.123l-2.432-2.432z" /></svg>`,
    Projects: `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z" /></svg>`,
    'Apply for Leave': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>`,
    'Working Hours': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>`,
    'Manage Users': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    'Manage Teams': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" /></svg>`,
    'Company Hierarchy': `<svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" /></svg>`,
};
</script>

<template>
    <div class="min-h-screen bg-slate-50 font-sans">
        <!-- Static sidebar for desktop -->
        <div class="hidden md:fixed md:inset-y-0 md:flex md:w-64 md:flex-col">
            <div class="flex flex-grow flex-col overflow-y-auto border-r border-slate-200 bg-white pt-5">
                <div class="flex flex-shrink-0 items-center px-4 space-x-2">
                    <Link :href="route('dashboard')">
                        <ApplicationLogo class="block h-8 w-auto text-slate-800" />
                    </Link>
                    <span class="text-xl font-bold text-slate-800">WorkSphere</span>
                </div>
                <nav v-if="user && user.permissions" class="mt-8 flex-1 space-y-1 px-3 pb-4">
                    <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
                        <span v-html="icons['Dashboard']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('dashboard') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Dashboard
                    </NavLink>
                    <NavLink v-if="user.permissions.includes('manage leave applications')" :href="route('leaves.calendar')" :active="route().current('leaves.calendar')">
                        <span v-html="icons['Leave Calendar']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('leaves.calendar') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Leave Calendar
                    </NavLink>
                    <NavLink v-if="user.permissions.includes('manage roles')" :href="route('roles.index')" :active="route().current('roles.index')">
                        <span v-html="icons['Manage Roles']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('roles.index') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Manage Roles
                    </NavLink>
                    <NavLink v-if="user.permissions.includes('assign projects') || user.permissions.includes('view all projects progress')" :href="route('projects.index')" :active="route().current('projects.*')">
                        <span v-html="icons['Projects']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('projects.*') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Projects
                    </NavLink>
                    <NavLink v-if="user.permissions.includes('apply for leave')" :href="route('leave.index')" :active="route().current('leave.*')">
                        <span v-html="icons['Apply for Leave']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('leave.*') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Apply for Leave
                    </NavLink>
                    <NavLink :href="route('hours.index')" :active="route().current('hours.index')">
                        <span v-html="icons['Working Hours']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('hours.index') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Working Hours
                    </NavLink>
                    <NavLink v-if="user.permissions.includes('manage employees')" :href="route('users.index')" :active="route().current('users.index')">
                        <span v-html="icons['Manage Users']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('users.index') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Manage Users
                    </NavLink>
                    <NavLink v-if="user.permissions.includes('manage employees')" :href="route('teams.index')" :active="route().current('teams.index')">
                        <span v-html="icons['Manage Teams']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('teams.index') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Manage Teams
                    </NavLink>
                    <NavLink :href="route('company.hierarchy')" :active="route().current('company.hierarchy')">
                        <span v-html="icons['Company Hierarchy']" class="mr-3 flex-shrink-0 h-6 w-6" :class="[route().current('company.hierarchy') ? 'text-slate-700' : 'text-slate-400 group-hover:text-slate-500']"></span>
                        Company Hierarchy
                    </NavLink>
                </nav>
            </div>
        </div>

        <!-- Main content area -->
        <div class="md:pl-64">
            <div class="mx-auto flex max-w-7xl flex-col min-h-screen">
                <!-- Top bar -->
                <header class="sticky top-0 z-20 flex h-16 flex-shrink-0 justify-between border-b border-slate-200 bg-white px-4 sm:px-6 lg:px-8">
                    <button @click="showingSidebar = true" type="button" class="border-r border-slate-200 px-4 text-slate-500 focus:outline-none md:hidden">
                        <span class="sr-only">Open sidebar</span>
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" /></svg>
                    </button>
                    <div class="flex items-center">
                        <slot name="header" />
                    </div>
                    <div class="flex flex-1 items-center justify-end space-x-4">
                        <template v-if="user">
                            <!-- Notification Dropdown -->
                            <div class="relative">
                                <!-- ... notification button and dropdown content ... -->
                            </div>
                            <!-- User Profile Dropdown -->
                            <Dropdown align="right" width="48">
                                <template #trigger>
                                    <button class="flex items-center space-x-3 rounded-lg p-1 transition hover:bg-slate-50">
                                        <template v-if="user && user.image">
                                        <img
                                            class="h-9 w-9 rounded-full object-cover"
                                            :src="`/storage/${user.image}`"
                                            alt="User Avatar"
                                        />
                                        </template>
                                        <template v-else>
                                        <!-- If no user image, always show the default avatar image -->
                                        <img
                                            class="h-9 w-9 rounded-full object-cover"
                                            src="/storage/defaults/default-avatar.jpg"
                                            alt="Default Avatar"
                                        />
                                        </template>
                                        <div class="hidden text-left md:block">
                                        <div class="text-sm font-semibold text-slate-800">{{ user.name }}</div>
                                        <div class="text-xs text-slate-500">{{ userDesignation }}</div>
                                        </div>
                                        <svg class="h-5 w-5 text-slate-400" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                    </template>


                                <template #content>
                                    <DropdownLink :href="route('profile.edit')"> Profile </DropdownLink>
                                    <DropdownLink :href="route('notifications.index')">Notifications</DropdownLink>
                                    <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
                                </template>
                            </Dropdown>
                        </template>
                    </div>
                </header>
                <main class="flex-1">
                    <slot />
                </main>
            </div>
        </div>
        
        <!-- Mobile Sidebar (Overlay) -->
        <div v-if="showingSidebar" class="relative z-40 md-hidden" role="dialog" aria-modal="true">
            <!-- ... mobile sidebar template ... -->
        </div>
    </div>
</template>

<style>
/* Simplified NavLink styling for consistency */
.nav-link {
    display: flex;
    align-items: center;
    width: 100%;
    padding: 0.625rem 0.75rem;
    border-radius: 0.375rem;
    font-size: 0.875rem;
    font-weight: 500;
    color: #475569;
    transition: background-color 0.15s ease-in-out, color 0.15s ease-in-out;
}
.nav-link:hover {
    background-color: #f8fafc;
    color: #1e2937;
}
.nav-link[aria-current="page"] {
    background-color: #f1f5f9;
    color: #0f172a;
}
.nav-link[aria-current="page"] .flex-shrink-0 {
    color: #334155;
}
.nav-link .flex-shrink-0 {
    color: #94a3b8;
}
.nav-link:hover .flex-shrink-0 {
    color: #64748b;
}
</style>