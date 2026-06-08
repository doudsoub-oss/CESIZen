<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import MenuItemController from '@/actions/App/Http/Controllers/Admin/MenuItemController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import { edit as editMenu, index as indexMenus } from '@/routes/admin/menus';
import type { BreadcrumbItem, Menu, MenuItem } from '@/types';

const props = defineProps<{
    menu: Menu;
    item: MenuItem;
    parentOptions: Array<{ id: number; title: string }>;
    contentOptions: Array<{ id: number; label: string }>;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Menus', href: indexMenus() },
    { title: props.menu.name, href: editMenu(props.menu.id) },
    { title: props.item.title, href: editMenu(props.menu.id) },
]);
</script>

<template>
    <Head title="Éditer l'entrée de menu" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-6">
            <Link
                :href="editMenu(menu.id)"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour au menu
            </Link>
            <h1 class="text-2xl font-semibold tracking-tight">
                Éditer l'entrée
            </h1>

            <Form
                v-bind="
                    MenuItemController.update.form({
                        menu: menu.id,
                        item: item.id,
                    })
                "
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Libellé</Label>
                    <Input
                        id="title"
                        name="title"
                        required
                        autocomplete="off"
                        :default-value="item.title"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="content_id">
                        Contenu lié
                        <span class="text-xs text-muted-foreground">
                            (le lien est construit à partir du contenu choisi)
                        </span>
                    </Label>
                    <select
                        id="content_id"
                        name="content_id"
                        required
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="" disabled :selected="!item.content_id">
                            Choisir un contenu…
                        </option>
                        <option
                            v-for="option in contentOptions"
                            :key="option.id"
                            :value="option.id"
                            :selected="option.id === item.content_id"
                        >
                            {{ option.label }}
                        </option>
                    </select>
                    <InputError :message="errors.content_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="parent_id">Entrée parente</Label>
                    <select
                        id="parent_id"
                        name="parent_id"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option value="" :selected="item.parent_id === null">
                            Aucune (niveau supérieur)
                        </option>
                        <option
                            v-for="option in parentOptions"
                            :key="option.id"
                            :value="option.id"
                            :selected="option.id === item.parent_id"
                        >
                            {{ option.title }}
                        </option>
                    </select>
                    <InputError :message="errors.parent_id" />
                </div>

                <div class="grid gap-2">
                    <Label for="position">Position</Label>
                    <Input
                        id="position"
                        name="position"
                        type="number"
                        min="0"
                        :default-value="item.position"
                    />
                    <InputError :message="errors.position" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="size-4 accent-primary"
                        :checked="item.is_active"
                    />
                    Visible
                </label>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link :href="editMenu(menu.id)">Annuler</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Sauvegarder
                    </Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
