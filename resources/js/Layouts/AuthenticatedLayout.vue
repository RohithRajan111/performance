<script setup>
import { ref } from 'vue';
import ApplicationLogo from '@/Components/ApplicationLogo.vue';
import Dropdown from '@/Components/Dropdown.vue';
import DropdownLink from '@/Components/DropdownLink.vue';
import NavLink from '@/Components/NavLink.vue';
import { Link } from '@inertiajs/vue3';

const showingSidebar = ref(false);
</script>

<template>
  <div class="flex min-h-screen bg-gradient-to-tr from-indigo-50 via-white to-emerald-50 text-gray-800">
    <!-- Sidebar -->
    <aside class="w-72 bg-white border-r shadow-lg flex-col hidden md:flex">
      <div class="flex items-center justify-center h-16 border-b px-4">
        <Link :href="route('dashboard')">
          <ApplicationLogo class="h-9 w-auto text-indigo-600" />
        </Link>
      </div>
      <nav class="flex flex-col gap-2 py-6 px-4">
        <NavLink :href="route('dashboard')" :active="route().current('dashboard')">
          <span class="mr-2">🏠</span> Dashboard
        </NavLink>
        <NavLink v-if="$page.props.auth.user.permissions.includes('manage leave applications')" :href="route('leaves.calendar')" :active="route().current('leaves.calendar')">
                        Leave Calendar
                    </NavLink>
        <NavLink v-if="$page.props.auth.user.permissions.includes('manage roles')" :href="route('roles.index')" :active="route().current('roles.index')">
          <span class="mr-2">🛡️</span> Manage Roles
        </NavLink>
        <NavLink v-if="$page.props.auth.user.permissions.includes('assign projects')" :href="route('projects.create')" :active="route().current('projects.create')">
          <span class="mr-2">📁</span> New Project
        </NavLink>
        <NavLink v-if="$page.props.auth.user.permissions.includes('apply for leave')" :href="route('leave.index')" :active="route().current('leave.index')">
          <span class="mr-2">🌴</span> Apply Leave
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
      </nav>
    </aside>

    <!-- Main Area -->
    <div class="flex flex-1 flex-col min-h-screen">
      <!-- Top Navigation Bar -->
      <header class="bg-white border-b flex justify-between items-center shadow h-16 px-6">
        <!-- Mobile Sidebar Trigger -->
        <button class="md:hidden flex items-center text-gray-600 hover:text-indigo-600" @click="showingSidebar = !showingSidebar">
          <svg class="h-7 w-7" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <div class="font-semibold text-lg text-gray-800">
          <slot name="header" />
        </div>
        <div>
          <Dropdown align="right" width="48">
            <template #trigger>
              <button class="flex items-center px-3 py-2 rounded transition hover:bg-gray-100 text-gray-700 font-medium">
                {{ $page.props.auth.user.name }}
                <svg class="ml-2 w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                  <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 
                  1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
                </svg>
              </button>
            </template>
            <template #content>
              <DropdownLink :href="route('profile.edit')">Profile</DropdownLink>
              <DropdownLink :href="route('logout')" method="post" as="button">Log Out</DropdownLink>
            </template>
          </Dropdown>
        </div>
      </header>

      <!-- Main Content -->
      <main class="flex-1 px-2 md:px-8 py-6 bg-gradient-to-br from-indigo-50 via-white to-emerald-50">
        <slot />
      </main>
    </div>
  </div>
</template>
