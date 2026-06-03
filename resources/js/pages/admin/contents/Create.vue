<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import ContentController from '@/actions/App/Http/Controllers/Admin/ContentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    create as createContent,
    index as indexContents,
} from '@/routes/admin/contents';
import type { BreadcrumbItem, IdNameOption, SelectOption } from '@/types';

defineProps<{
    categories: IdNameOption[];
    types: SelectOption[];
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Contenus', href: indexContents() },
    { title: 'Nouveau', href: createContent() },
];
</script>

<template>
    <Head title="Nouveau contenu" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-3xl p-6">
            <Link
                :href="indexContents()"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>
            <h1 class="text-2xl font-semibold tracking-tight">
                Nouveau contenu
            </h1>

            <Form
                v-bind="ContentController.store.form()"
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Titre</Label>
                    <Input
                        id="title"
                        name="title"
                        required
                        autocomplete="off"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">
                        Slug
                        <span class="text-xs text-muted-foreground">
                            (laisser vide pour générer automatiquement)
                        </span>
                    </Label>
                    <Input id="slug" name="slug" autocomplete="off" />
                    <InputError :message="errors.slug" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="type">Type</Label>
                        <select
                            id="type"
                            name="type"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option
                                v-for="option in types"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.label }}
                            </option>
                        </select>
                        <InputError :message="errors.type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="category_id">
                            Catégorie
                            <span class="text-xs text-muted-foreground">
                                (requise sauf pour une page)
                            </span>
                        </Label>
                        <select
                            id="category_id"
                            name="category_id"
                            class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            <option value="">Aucune (page autonome)</option>
                            <option
                                v-for="option in categories"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                            </option>
                        </select>
                        <InputError :message="errors.category_id" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="excerpt">Résumé</Label>
                    <textarea
                        id="excerpt"
                        name="excerpt"
                        rows="2"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <InputError :message="errors.excerpt" />
                </div>

                <div class="grid gap-2">
                    <Label for="body">
                        Corps
                        <span class="text-xs text-muted-foreground">
                            (Markdown : ## titres, **gras**, listes…)
                        </span>
                    </Label>
                    <textarea
                        id="body"
                        name="body"
                        rows="14"
                        required
                        class="rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <InputError :message="errors.body" />
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="published_at">
                            Date de publication
                            <span class="text-xs text-muted-foreground">
                                (optionnelle)
                            </span>
                        </Label>
                        <Input
                            id="published_at"
                            name="published_at"
                            type="date"
                        />
                        <InputError :message="errors.published_at" />
                    </div>

                    <label
                        class="flex items-center gap-2 self-end pb-2 text-sm"
                    >
                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            class="size-4 accent-primary"
                            checked
                        />
                        Publié
                    </label>
                </div>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link :href="indexContents()">Annuler</Link>
                    </Button>
                    <Button type="submit" :disabled="processing">Créer</Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
