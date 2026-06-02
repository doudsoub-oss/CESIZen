<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as indexUsers, store as storeUser } from '@/routes/admin/users';
import type { BreadcrumbItem, RoleOption } from '@/types';

defineProps<{
    roleOptions: RoleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Utilisateurs', href: indexUsers() },
    { title: 'Nouveau', href: indexUsers() },
];
</script>

<template>
    <Head title="Nouvel utilisateur" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-6">
            <Link
                :href="indexUsers()"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>
            <h1 class="text-2xl font-semibold tracking-tight">
                Nouvel utilisateur
            </h1>

            <Form
                v-bind="storeUser.form()"
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nom</Label>
                    <Input id="name" name="name" required autocomplete="off" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="email">Email</Label>
                    <Input
                        id="email"
                        name="email"
                        type="email"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Mot de passe</Label>
                    <Input
                        id="password"
                        name="password"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password" />
                </div>

                <div class="grid gap-2">
                    <Label for="password_confirmation">
                        Confirmation du mot de passe
                    </Label>
                    <Input
                        id="password_confirmation"
                        name="password_confirmation"
                        type="password"
                        required
                        autocomplete="new-password"
                    />
                    <InputError :message="errors.password_confirmation" />
                </div>

                <div class="grid gap-2">
                    <Label for="role">Rôle</Label>
                    <select
                        id="role"
                        name="role"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option
                            v-for="option in roleOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.role" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="size-4 accent-primary"
                        checked
                    />
                    Compte actif
                </label>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link :href="indexUsers()">Annuler</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">Créer</Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
