<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import CategoryController from '@/actions/App/Http/Controllers/Admin/CategoryController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    edit as editCategory,
    index as indexCategories,
} from '@/routes/admin/categories';
import type { BreadcrumbItem, CategorySummary, IdNameOption } from '@/types';

const props = defineProps<{
    category: CategorySummary;
    parentOptions: IdNameOption[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Catégories', href: indexCategories() },
    { title: props.category.name, href: editCategory(props.category.id) },
]);
</script>

<template>
    <Head :title="`Éditer ${category.name}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-6">
            <Link
                :href="indexCategories()"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>
            <h1 class="text-2xl font-semibold tracking-tight">
                Éditer la catégorie
            </h1>

            <Form
                v-bind="
                    CategoryController.update.form({ category: category.id })
                "
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="name">Nom</Label>
                    <Input
                        id="name"
                        name="name"
                        required
                        autocomplete="off"
                        :default-value="category.name"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        name="slug"
                        required
                        autocomplete="off"
                        :default-value="category.slug"
                    />
                    <InputError :message="errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        :value="category.description ?? ''"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="parent_id">Catégorie parente</Label>
                    <select
                        id="parent_id"
                        name="parent_id"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <option
                            value=""
                            :selected="category.parent_id === null"
                        >
                            Aucune (catégorie racine)
                        </option>
                        <option
                            v-for="option in parentOptions"
                            :key="option.id"
                            :value="option.id"
                            :selected="option.id === category.parent_id"
                        >
                            {{ option.name }}
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
                        :default-value="category.position"
                    />
                    <InputError :message="errors.position" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="size-4 accent-primary"
                        :checked="category.is_active"
                    />
                    Active (visible sur le site public)
                </label>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link :href="indexCategories()">Annuler</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Sauvegarder
                    </Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
