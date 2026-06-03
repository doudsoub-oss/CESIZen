<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import MenuController from '@/actions/App/Http/Controllers/Admin/MenuController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createMenu,
    index as indexMenus,
} from '@/routes/admin/menus';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Menus', href: indexMenus() },
    { title: 'Nouveau', href: createMenu() },
];
</script>

<template>
    <Head title="Nouveau menu" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-6">
            <Link
                :href="indexMenus()"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>
            <h1 class="text-2xl font-semibold tracking-tight">Nouveau menu</h1>

            <Form
                v-bind="MenuController.store.form()"
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nom</Label>
                    <Input id="name" name="name" required autocomplete="off" />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="location">Emplacement</Label>
                    <select
                        id="location"
                        name="location"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="main">Principal (en-tête)</option>
                        <option value="footer">Pied de page</option>
                        <option value="sidebar">Latéral</option>
                    </select>
                    <InputError :message="errors.location" />
                </div>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link :href="indexMenus()">Annuler</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">Créer</Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
