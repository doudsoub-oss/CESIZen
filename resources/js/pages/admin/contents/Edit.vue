<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, ExternalLink } from 'lucide-vue-next';
import { computed } from 'vue';
import ContentController from '@/actions/App/Http/Controllers/Admin/ContentController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    edit as editContent,
    index as indexContents,
} from '@/routes/admin/contents';
import type {
    BreadcrumbItem,
    ContentDetail,
    IdNameOption,
    SelectOption,
} from '@/types';

const props = defineProps<{
    content: ContentDetail;
    categories: IdNameOption[];
    types: SelectOption[];
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Contenus', href: indexContents() },
    { title: props.content.title, href: editContent(props.content.id) },
]);

const publishedDate = computed(() => props.content.published_at?.slice(0, 10));
</script>

<template>
    <Head :title="`Éditer ${content.title}`" />

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
                Éditer le contenu
            </h1>

            <Form
                v-bind="ContentController.update.form({ content: content.id })"
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
                        :default-value="content.title"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        name="slug"
                        required
                        autocomplete="off"
                        :default-value="content.slug"
                    />
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
                                :selected="option.value === content.type"
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
                            <option
                                value=""
                                :selected="content.category_id === null"
                            >
                                Aucune (page autonome)
                            </option>
                            <option
                                v-for="option in categories"
                                :key="option.id"
                                :value="option.id"
                                :selected="option.id === content.category_id"
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
                        :value="content.excerpt ?? ''"
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
                        :value="content.body"
                        class="rounded-md border border-input bg-background px-3 py-2 font-mono text-sm focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    />
                    <InputError :message="errors.body" />
                </div>

                <div class="grid gap-2">
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            type="checkbox"
                            name="is_published"
                            value="1"
                            class="size-4 accent-primary"
                            :checked="content.is_published"
                        />
                        Publié
                    </label>
                    <p
                        v-if="publishedDate"
                        class="text-xs text-muted-foreground"
                    >
                        Date de publication (automatique) : {{ publishedDate }}
                    </p>
                </div>

                <div class="flex items-center justify-between gap-2">
                    <a
                        v-if="content.type === 'page'"
                        :href="`/pages/${content.slug}`"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        <ExternalLink class="size-4" />
                        Voir la page
                    </a>
                    <span v-else />
                    <div class="flex justify-end gap-2">
                        <Button as-child variant="ghost">
                            <Link :href="indexContents()">Annuler</Link>
                        </Button>
                        <Button type="submit" :disabled="processing">
                            Sauvegarder
                        </Button>
                    </div>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
