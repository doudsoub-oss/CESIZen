<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { reactive, watch } from 'vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { index as auditLogsIndex } from '@/routes/admin/audit-logs';
import type { AuditLogEntry, BreadcrumbItem, Paginator } from '@/types';

const props = defineProps<{
    logs: Paginator<AuditLogEntry>;
    filters: {
        action: string | null;
        user_id: number | null;
        type: string | null;
        from: string | null;
        to: string | null;
    };
    actionOptions: string[];
    typeOptions: { value: string; label: string }[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: "Journaux d'audit", href: auditLogsIndex() },
];

const selected = reactive({
    action: props.filters.action ?? '',
    type: props.filters.type ?? '',
    from: props.filters.from ?? '',
    to: props.filters.to ?? '',
});

watch(selected, (value) => {
    const query: Record<string, string> = {};

    if (value.action !== '') {
        query.action = value.action;
    }

    if (value.type !== '') {
        query.type = value.type;
    }

    if (value.from !== '') {
        query.from = value.from;
    }

    if (value.to !== '') {
        query.to = value.to;
    }

    router.get(auditLogsIndex().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
});

const dateFormatter = new Intl.DateTimeFormat('fr-FR', {
    dateStyle: 'short',
    timeStyle: 'short',
});

const formatDate = (value: string): string =>
    dateFormatter.format(new Date(value));

const shortType = (type: string | null): string =>
    type ? (type.split('\\').pop() ?? type) : '—';

const isAuthAction = (action: string): boolean => action.startsWith('auth.');

const formatValues = (values: Record<string, unknown> | null): string =>
    values && Object.keys(values).length
        ? Object.entries(values)
              .map(([key, val]) => `${key}: ${JSON.stringify(val)}`)
              .join('\n')
        : '—';
</script>

<template>
    <Head title="Journaux d'audit" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-col gap-6 p-6">
            <header>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Journaux d'audit
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ logs.total }} entrée{{ logs.total > 1 ? 's' : '' }} —
                    traçabilité RGPD des actions sensibles. Les valeurs
                    sensibles (mot de passe, secrets 2FA) sont masquées.
                </p>
            </header>

            <div class="flex flex-wrap items-end gap-4">
                <div class="grid gap-1.5">
                    <label
                        for="filter-action"
                        class="text-xs font-medium text-muted-foreground"
                    >
                        Action
                    </label>
                    <select
                        id="filter-action"
                        v-model="selected.action"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="">Toutes</option>
                        <option
                            v-for="option in actionOptions"
                            :key="option"
                            :value="option"
                        >
                            {{ option }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <label
                        for="filter-type"
                        class="text-xs font-medium text-muted-foreground"
                    >
                        Type de cible
                    </label>
                    <select
                        id="filter-type"
                        v-model="selected.type"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="">Tous</option>
                        <option
                            v-for="option in typeOptions"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                </div>
                <div class="grid gap-1.5">
                    <label
                        for="filter-from"
                        class="text-xs font-medium text-muted-foreground"
                    >
                        Du
                    </label>
                    <input
                        id="filter-from"
                        v-model="selected.from"
                        type="date"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                </div>
                <div class="grid gap-1.5">
                    <label
                        for="filter-to"
                        class="text-xs font-medium text-muted-foreground"
                    >
                        Au
                    </label>
                    <input
                        id="filter-to"
                        v-model="selected.to"
                        type="date"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                </div>
            </div>

            <div
                class="overflow-x-auto rounded-xl border border-border bg-card shadow-sm"
            >
                <table class="w-full text-sm">
                    <thead
                        class="bg-muted/40 text-left text-xs tracking-wider text-muted-foreground uppercase"
                    >
                        <tr>
                            <th class="px-4 py-3">Date</th>
                            <th class="px-4 py-3">Acteur</th>
                            <th class="px-4 py-3">Action</th>
                            <th class="px-4 py-3">Cible</th>
                            <th class="px-4 py-3">Détails</th>
                            <th class="px-4 py-3">IP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border">
                        <tr
                            v-for="log in logs.data"
                            :key="log.id"
                            class="align-top hover:bg-muted/30"
                        >
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ formatDate(log.created_at) }}
                            </td>
                            <td class="px-4 py-3">
                                <span v-if="log.user">
                                    {{ log.user.name }}
                                    <span
                                        class="block text-xs text-muted-foreground"
                                    >
                                        {{ log.user.email }}
                                    </span>
                                </span>
                                <span v-else class="text-muted-foreground">
                                    Anonyme / inconnu
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <Badge
                                    :variant="
                                        isAuthAction(log.action)
                                            ? 'secondary'
                                            : 'default'
                                    "
                                >
                                    {{ log.action }}
                                </Badge>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <span v-if="log.auditable_type">
                                    {{ shortType(log.auditable_type) }}
                                    <span class="text-muted-foreground"
                                        >#{{ log.auditable_id }}</span
                                    >
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="log.old_values || log.new_values"
                                    class="grid gap-1 text-xs"
                                >
                                    <pre
                                        v-if="log.old_values"
                                        class="max-w-xs whitespace-pre-wrap text-muted-foreground"
                                    >
− {{ formatValues(log.old_values) }}</pre
                                    >
                                    <pre
                                        v-if="log.new_values"
                                        class="max-w-xs whitespace-pre-wrap"
                                    >
+ {{ formatValues(log.new_values) }}</pre
                                    >
                                </div>
                                <span v-else class="text-muted-foreground"
                                    >—</span
                                >
                            </td>
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                            >
                                {{ log.ip_address ?? '—' }}
                            </td>
                        </tr>
                        <tr v-if="!logs.data.length">
                            <td
                                colspan="6"
                                class="px-4 py-6 text-center text-sm text-muted-foreground"
                            >
                                Aucune entrée ne correspond aux filtres.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Pagination :links="logs.links" :last-page="logs.last_page" />
        </div>
    </AdminLayout>
</template>
