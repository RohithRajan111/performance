<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// The props are passed from the RoleController@index method
const props = defineProps({
    roles: Array,
    allPermissions: Array,
});

// Set up the form for creating a new role
const form = useForm({
    name: '',
    permissions: [], // Initialize as an empty array for the checkboxes
});

// Function to handle the form submission
const submit = () => {
    form.post(route('roles.store'), {
        onSuccess: () => form.reset(), // Clear the form on success
    });
};
</script>

<template>
    <Head title="Manage Roles" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Manage Roles & Permissions</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 grid md:grid-cols-2 gap-6">

                <!-- Left Column: Form to Add New Role -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Add New Role</h2>
                            <p class="mt-1 text-sm text-gray-600">
                                Create a new job role and assign the permissions it should have.
                            </p>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Role Name" />
                                <TextInput
                                    id="name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.name"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Permissions Checkbox Section -->
                            <div>
                                <InputLabel value="Assign Permissions" />
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4 border p-4 rounded-md h-60 overflow-y-auto">
                                    <div v-for="permission in allPermissions" :key="permission" class="flex items-center">
                                        <Checkbox
                                            :id="'permission_' + permission"
                                            :value="permission"
                                            v-model:checked="form.permissions"
                                        />
                                        <label :for="'permission_' + permission" class="ms-2 text-sm text-gray-600 capitalize">{{ permission.replaceAll('-', ' ') }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.permissions" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Create Role</PrimaryButton>
                                <Transition enter-from-class="opacity-0" leave-to-class="opacity-0" class="transition ease-in-out">
                                    <p v-if="form.recentlySuccessful" class="text-sm text-gray-600">Created.</p>
                                </Transition>
                            </div>
                        </form>
                    </section>
                </div>

                <!-- Right Column: List of Existing Roles -->
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Existing Roles</h2>
                        </header>
                        <div class="mt-4 space-y-4">
                             <div v-for="role in props.roles" :key="role.id" class="px-4 py-3 bg-gray-50 rounded-md border">
                                <div class="flex justify-between items-center">
                                    <h3 class="font-semibold capitalize">{{ role.name }}</h3>
                                    <!-- Edit Link to the new 'roles.edit' route -->
                                    <Link v-if="role.name !== 'admin'" :href="route('roles.edit', role.id)" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 hover:underline">
                                        Edit
                                    </Link>
                                </div>
                                <div class="mt-2 flex flex-wrap gap-2">
                                    <!-- Display assigned permissions for a quick overview -->
                                    <span v-for="permission in role.permissions" :key="permission" class="px-2 py-1 text-xs bg-indigo-100 text-indigo-700 rounded-full">
                                        {{ permission.replaceAll('-', ' ') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </div>
    </AuthenticatedLayout>
</template>