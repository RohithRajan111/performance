<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3'; // Import router
import DangerButton from '@/Components/DangerButton.vue'; // Optional: for styling delete

defineProps({ users: Object });

// Function to handle user deletion
const deleteUser = (userId) => {
    if (confirm('Are you sure you want to delete this employee?')) {
        router.delete(route('users.destroy', userId), {
            preserveScroll: true, // Keep the user on the same page
        });
    }
};
</script>

<template>
    <Head title="Manage Employees" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex justify-between items-center">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Employees</h2>
                <Link :href="route('users.create')" class="px-4 py-2 bg-indigo-600 text-white rounded-md shadow-sm hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Add Employee
                </Link>
            </div>
        </template>
        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Role</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <tr v-for="user in users.data" :key="user.id">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">{{ user.name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">{{ user.email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 capitalize">{{ user.roles.map(r => r.name).join(', ') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-4">
                                    <Link :href="route('users.edit', user.id)" class="text-indigo-600 hover:text-indigo-900">Edit</Link>
                                    <button @click="deleteUser(user.id)" class="text-red-600 hover:text-red-900">Delete</button>
                                    <!-- You can keep the performance link too -->
                                    <Link :href="route('performance.show', user.id)" class="text-green-600 hover:text-green-900">Performance</Link>
                                </td>
                            </tr>
                            <tr v-if="users.data.length === 0">
                                <td class="px-6 py-4 text-center text-sm text-gray-500" colspan="4">No employees found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>