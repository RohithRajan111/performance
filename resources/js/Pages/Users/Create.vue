<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({ roles: Array });

const form = useForm({
    name: '',
    email: '',
    password: '',
    role: '',
});
const submit = () => { form.post(route('users.store'), { onFinish: () => form.reset('password'), }); };
</script>
<template>
    <Head title="Add Employee" />
    <AuthenticatedLayout>
         <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Add New Employee</h2>
        </template>
        <div class="py-12">
            <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <form @submit.prevent="submit" class="space-y-6">
                        <div><InputLabel for="name" value="Name" /><TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required /><InputError class="mt-2" :message="form.errors.name" /></div>
                        <div><InputLabel for="email" value="Email" /><TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required /><InputError class="mt-2" :message="form.errors.email" /></div>
                        <div><InputLabel for="password" value="Password" /><TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required /><InputError class="mt-2" :message="form.errors.password" /></div>
                        <div>
                            <InputLabel for="role" value="Role" />
                            <select id="role" v-model="form.role" required>
                                <option value="" disabled>Select a role</option>
                                <!-- It loops through the 'roles' prop from the controller -->
                                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.role" />
                        </div>
                        <div class="flex items-center gap-4">
                            <PrimaryButton :disabled="form.processing">Create Employee</PrimaryButton>
                            <Link :href="route('users.index')" class="text-sm text-gray-600 hover:underline">Cancel</Link>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>