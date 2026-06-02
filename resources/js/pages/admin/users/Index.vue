<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus } from 'lucide-vue-next';
import { reactive, watch } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createUser,
    index as indexUsers,
    show as showUser,
} from '@/routes/admin/users';
import type {
    BreadcrumbItem,
    ManagedUser,
    Paginator,
    RoleOption,
} from '@/types';

const props = defineProps<{
    users: Paginator<ManagedUser>;
    filters: { role: string | null; active: boolean | null };
    roleOptions: RoleOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Utilisateurs', href: indexUsers() },
];

const roleLabels: Record<string, string> = {
    user: 'Utilisateur',
    admin: 'Administrateur',
    super_admin: 'Super-administrateur',
};

const selected = reactive({
    role: props.filters.role ?? '',
    active:
        props.filters.active === null ? '' : props.filters.active ? '1' : '0',
});

watch(selected, (value) => {
    const query: Record<string, string> = {};

    if (value.role !== '') {
        query.role = value.role;
    }

    if (value.active !== '') {
        query.active = value.active;
    }

    router.get(indexUsers().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});
</script>

<template>
    <Head title="Utilisateurs" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header class="flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight">
                        Utilisateurs
                    </h1>
                    <p class="text-sm text-muted-foreground">
                        {{ users.total }} compte{{
                            users.total > 1 ? 's' : ''
                        }}.
                    </p>
                </div>
                <Button as-child>
                    <Link :href="createUser()">
                        <Plus class="size-4" />
                        Nouvel utilisateur
                    </Link>
                </Button>
            </header>

            <div class="flex flex-wrap items-end gap-4">
                <div class="grid gap-1.5">
                    <label
                        for="filter-role"
                        class="text-xs font-medium text-muted-foreground"
                    >
                        Rôle
                    </label>
                    <select
                        id="filter-role"
                        v-model="selected.role"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="option in roleOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <label
                        for="filter-active"
                        class="text-xs font-medium text-muted-foreground"
                    >
                        État
                    </label>
                    <select
                        id="filter-active"
                        v-model="selected.active"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="">Tous</option>
                        <option value="1">Actifs</option>
                        <option value="0">Inactifs</option>
                    </select>
                </div>
            </div>

            <div
                class="overflow-hidden rounded-xl border border-border bg-card shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/40 text-left text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Nom</th>
                            <th class="px-4 py-3">Email</th>
                            <th class="px-4 py-3">Rôle</th>
                            <th class="px-4 py-3">État</th>
                            <th class="px-4 py-3 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 font-medium">
                                {{ user.name }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.email }}
                            </td>
                            <td class="px-4 py-3">
                                {{ roleLabels[user.role] ?? user.role }}
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        user.is_active ? 'default' : 'secondary'
                                    "
                                >
                                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <Button
                                    size="icon"
                                    variant="ghost"
                                    as-child
                                    aria-label="Voir le compte"
                                >
                                    <Link :href="showUser(user.id)">
                                        <Eye class="size-4" />
                                    </Link>
                                </Button>
                            </td>
                        </tr>
                        <tr v-if="!users.data.length">
                            <td
                                colspan="5"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Aucun utilisateur ne correspond aux filtres.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="users.links" :last-page="users.last_page" />
        </div>
    </AdminLayout>
</template>
