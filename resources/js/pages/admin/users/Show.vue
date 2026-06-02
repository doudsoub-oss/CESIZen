<script setup lang="ts">
import { Form, Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    changeRole,
    destroy as destroyUser,
    index as indexUsers,
    toggleActive,
} from '@/routes/admin/users';
import type { BreadcrumbItem, ManagedUser, RoleOption } from '@/types';

const props = defineProps<{
    user: ManagedUser;
    roleOptions: RoleOption[];
    can: { changeRole: boolean; toggleActive: boolean; delete: boolean };
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Utilisateurs', href: indexUsers() },
    { title: props.user.name, href: indexUsers() },
]);

const roleLabels: Record<string, string> = {
    user: 'Utilisateur',
    admin: 'Administrateur',
    super_admin: 'Super-administrateur',
};

function formatDate(raw: string | null | undefined): string {
    if (!raw) {
        return '—';
    }

    try {
        return new Intl.DateTimeFormat('fr-FR', { dateStyle: 'long' }).format(
            new Date(raw),
        );
    } catch {
        return raw;
    }
}

function confirmDelete() {
    if (
        window.confirm(
            `Supprimer définitivement le compte « ${props.user.name} » ?`,
        )
    ) {
        router.delete(destroyUser(props.user.id).url);
    }
}
</script>

<template>
    <Head :title="user.name" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-2xl flex-col gap-6 p-6">
            <Link
                :href="indexUsers()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>

            <header class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ user.name }}
                </h1>
                <Badge :variant="user.is_active ? 'default' : 'secondary'">
                    {{ user.is_active ? 'Actif' : 'Inactif' }}
                </Badge>
            </header>

            <dl
                class="grid gap-3 rounded-xl border border-border bg-card p-6 text-sm shadow-sm sm:grid-cols-2"
            >
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">
                        Email
                    </dt>
                    <dd class="mt-0.5">{{ user.email }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">
                        Rôle
                    </dt>
                    <dd class="mt-0.5">
                        {{ roleLabels[user.role] ?? user.role }}
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">
                        Diagnostics
                    </dt>
                    <dd class="mt-0.5">{{ user.diagnostics_count ?? 0 }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground uppercase">
                        Inscrit le
                    </dt>
                    <dd class="mt-0.5">{{ formatDate(user.created_at) }}</dd>
                </div>
            </dl>

            <section
                v-if="can.toggleActive || can.changeRole || can.delete"
                class="grid gap-6 rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <h2 class="text-lg font-semibold">Gestion du compte</h2>

                <Form
                    v-if="can.toggleActive"
                    v-bind="toggleActive.form(user.id)"
                    v-slot="{ processing }"
                    class="flex items-center justify-between gap-3"
                >
                    <div>
                        <p class="text-sm font-medium">
                            {{
                                user.is_active
                                    ? 'Compte actif'
                                    : 'Compte inactif'
                            }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Un compte inactif ne peut plus se connecter.
                        </p>
                    </div>
                    <Button
                        type="submit"
                        :variant="user.is_active ? 'outline' : 'default'"
                        :disabled="processing"
                    >
                        {{ user.is_active ? 'Désactiver' : 'Activer' }}
                    </Button>
                </Form>

                <Form
                    v-if="can.changeRole"
                    v-bind="changeRole.form(user.id)"
                    v-slot="{ errors, processing }"
                    class="grid gap-2"
                >
                    <Label for="role">Rôle</Label>
                    <div class="flex items-center gap-2">
                        <select
                            id="role"
                            name="role"
                            class="flex-1 rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option
                                v-for="option in roleOptions"
                                :key="option.value"
                                :value="option.value"
                                :selected="option.value === user.role"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <Button type="submit" :disabled="processing">
                            Enregistrer
                        </Button>
                    </div>
                    <InputError :message="errors.role" />
                </Form>

                <div
                    v-if="can.delete"
                    class="flex items-center justify-between gap-3 border-t border-border pt-4"
                >
                    <div>
                        <p class="text-sm font-medium">Supprimer le compte</p>
                        <p class="text-xs text-muted-foreground">
                            Action irréversible : toutes les données liées sont
                            supprimées.
                        </p>
                    </div>
                    <Button variant="destructive" @click="confirmDelete">
                        <Trash2 class="size-4" />
                        Supprimer
                    </Button>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
