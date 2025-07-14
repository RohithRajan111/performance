<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import Checkbox from '@/Components/Checkbox.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

// The props are passed from RoleController@edit
const props = defineProps({
    role: Object,
    allPermissions: Array,
});

// The form is pre-filled with the role's current data
const form = useForm({
    name: props.role.name,
    permissions: props.role.permissions, // This will pre-check the correct boxes
});

const submit = () => {
    // We use PUT/PATCH for updates
    form.put(route('roles.update', props.role.id));
};
</script>

<template>
    <Head :title="'Edit Role: ' + form.name" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Edit Role</h2>
        </template>

        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                    <section>
                        <header>
                            <h2 class="text-lg font-medium text-gray-900">Update Role Information</h2>
                        </header>

                        <form @submit.prevent="submit" class="mt-6 space-y-6">
                            <div>
                                <InputLabel for="name" value="Role Name" />
                                <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
                                <InputError class="mt-2" :message="form.errors.name" />
                            </div>

                            <!-- Permissions Checkbox Section -->
                            <div>
                                <InputLabel value="Update Permissions" />
                                <div class="mt-2 grid grid-cols-1 sm:grid-cols-2 gap-4 border p-4 rounded-md">
                                    <div v-for="permission in allPermissions" :key="permission" class="flex items-center">
                                        <Checkbox
                                            :id="'permission_' + permission"
                                            :value="permission"
                                            v-model:checked="form.permissions"
                                        />
                                        <label :for="'permission_' + permission" class="ms-2 text-sm text-gray-600 capitalize">{{ permission }}</label>
                                    </div>
                                </div>
                                <InputError class="mt-2" :message="form.errors.permissions" />
                            </div>

                            <div class="flex items-center gap-4">
                                <PrimaryButton :disabled="form.processing">Save Changes</PrimaryButton>
                                <Link :href="route('roles.index')" class="text-sm text-gray-600 hover:underline">Cancel</Link>
                            </div>
                        </form>
                    </section>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>