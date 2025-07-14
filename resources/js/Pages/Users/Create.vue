<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import InputError from '@/Components/InputError.vue';

defineProps({
  roles: Array,
  teams: Array, // Accept teams from controller
});

const form = useForm({
  name: '',
  email: '',
  password: '',
  role: '',
  team_id: '',
});

const submit = () => {
  form.post(route('users.store'), {
    onFinish: () => form.reset('password'),
  });
};
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
            <!-- Name -->
            <div>
              <InputLabel for="name" value="Name" />
              <TextInput id="name" type="text" class="mt-1 block w-full" v-model="form.name" required />
              <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <!-- Email -->
            <div>
              <InputLabel for="email" value="Email" />
              <TextInput id="email" type="email" class="mt-1 block w-full" v-model="form.email" required />
              <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <!-- Password -->
            <div>
              <InputLabel for="password" value="Password" />
              <TextInput id="password" type="password" class="mt-1 block w-full" v-model="form.password" required />
              <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <!-- Role -->
            <div>
              <InputLabel for="role" value="Role" />
              <select id="role" class="mt-1 block w-full" v-model="form.role" required>
                <option value="" disabled>Select a role</option>
                <option v-for="role in roles" :key="role" :value="role">{{ role }}</option>
              </select>
              <InputError class="mt-2" :message="form.errors.role" />
            </div>

            <!-- Team (conditionally shown if role === 'employee') -->
            <div v-if="form.role === 'employee'">
              <InputLabel for="team_id" value="Team" />
              <select id="team_id" class="mt-1 block w-full" v-model="form.team_id" required>
                <option value="" disabled>Select a team</option>
                <option v-for="team in teams" :key="team.id" :value="team.id">{{ team.name }}</option>
              </select>
              <InputError class="mt-2" :message="form.errors.team_id" />
            </div>

            <!-- Buttons -->
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
