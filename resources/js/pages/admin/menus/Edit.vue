<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import {
    ChevronLeft,
    CornerDownRight,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-vue-next';
import { computed } from 'vue';
import MenuController from '@/actions/App/Http/Controllers/Admin/MenuController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { edit as editMenu, index as indexMenus } from '@/routes/admin/menus';
import {
    create as createItem,
    destroy as destroyItem,
    edit as editItem,
} from '@/routes/admin/menus/items';
import type { BreadcrumbItem, Menu } from '@/types';

const props = defineProps<{
    menu: Menu;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Menus', href: indexMenus() },
    { title: props.menu.name, href: editMenu(props.menu.id) },
]);

const items = computed(() => props.menu.items ?? []);

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head :title="`Éditer ${menu.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
            <Link
                :href="indexMenus()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>

            <h1 class="text-2xl font-semibold tracking-tight">
                Éditer le menu
            </h1>

            <!-- Menu fields -->
            <Form
                v-bind="MenuController.update.form({ menu: menu.id })"
                class="grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nom</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        :default-value="menu.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="location">Emplacement</Label>
                    <select
                        id="location"
                        name="location"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option
                            value="main"
                            :selected="menu.location === 'main'"
                        >
                            Principal (en-tête)
                        </option>
                        <option
                            value="footer"
                            :selected="menu.location === 'footer'"
                        >
                            Pied de page
                        </option>
                        <option
                            value="sidebar"
                            :selected="menu.location === 'sidebar'"
                        >
                            Latéral
                        </option>
                    </select>
                    <InputError :message="errors.location" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        Sauvegarder
                    </Button>
                </div>
            </Form>

            <!-- Items panel -->
            <section
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <header class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">
                        Entrées ({{ items.length }})
                    </h2>
                    <Button as-child size="sm">
                        <Link :href="createItem(menu.id)">
                            <Plus class="size-4" />
                            Ajouter
                        </Link>
                    </Button>
                </header>

                <ul
                    v-if="items.length"
                    class="divide-y divide-border rounded-md border border-border"
                >
                    <li
                        v-for="item in items"
                        :key="item.id"
                        class="flex items-center justify-between gap-3 p-3"
                        :class="item.parent_id ? 'pl-8' : ''"
                    >
                        <div class="flex min-w-0 items-center gap-2">
                            <CornerDownRight
                                v-if="item.parent_id"
                                class="size-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm font-medium">
                                    {{ item.title }}
                                </p>
                                <p
                                    class="truncate font-mono text-xs text-muted-foreground"
                                >
                                    {{
                                        item.url ??
                                        `→ ${item.content?.title ?? 'contenu'}`
                                    }}
                                </p>
                            </div>
                        </div>
                        <div class="flex shrink-0 items-center gap-1">
                            <Badge v-if="!item.is_active" variant="secondary">
                                Masquée
                            </Badge>
                            <Button size="icon" variant="ghost" as-child>
                                <Link :href="editItem([menu.id, item.id])">
                                    <Pencil class="size-4" />
                                </Link>
                            </Button>
                            <Link
                                :href="destroyItem([menu.id, item.id]).url"
                                method="delete"
                                as="button"
                                class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                @click="
                                    (event) =>
                                        confirmDelete(
                                            event,
                                            `Supprimer l'entrée « ${item.title} » ?`,
                                        )
                                "
                            >
                                <Trash2 class="size-4" />
                            </Link>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground italic">
                    Aucune entrée. Ajoutez-en une pour composer la navigation.
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
