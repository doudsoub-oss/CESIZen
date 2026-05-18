<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import ResultInterpretationController from '@/actions/App/Http/Controllers/Admin/ResultInterpretationController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    edit as editQuestionnaire,
    index as indexQuestionnaires,
} from '@/routes/admin/questionnaires';
import type { BreadcrumbItem, Questionnaire } from '@/types';

const props = defineProps<{
    questionnaire: Questionnaire;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Questionnaires', href: indexQuestionnaires() },
    {
        title: props.questionnaire.title,
        href: editQuestionnaire(props.questionnaire.id),
    },
    {
        title: 'Nouvelle interprétation',
        href: editQuestionnaire(props.questionnaire.id),
    },
]);
</script>

<template>
    <Head title="Nouvelle interprétation" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-2xl p-6">
            <Link
                :href="editQuestionnaire(questionnaire.id)"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour au questionnaire
            </Link>

            <h1 class="text-2xl font-semibold tracking-tight">
                Ajouter une interprétation
            </h1>

            <Form
                v-bind="
                    ResultInterpretationController.store.form({
                        questionnaire: questionnaire.id,
                    })
                "
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid grid-cols-2 gap-4">
                    <div class="grid gap-2">
                        <Label for="min_score">Score min</Label>
                        <Input
                            id="min_score"
                            name="min_score"
                            type="number"
                            min="0"
                            required
                        />
                        <InputError :message="errors.min_score" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="max_score">Score max</Label>
                        <Input
                            id="max_score"
                            name="max_score"
                            type="number"
                            min="0"
                            required
                        />
                        <InputError :message="errors.max_score" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="title">Titre</Label>
                    <Input id="title" name="title" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="color">Couleur</Label>
                    <select
                        id="color"
                        name="color"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="">— Aucune —</option>
                        <option value="green">Vert (stress faible)</option>
                        <option value="yellow">Jaune (stress modéré)</option>
                        <option value="red">Rouge (stress élevé)</option>
                    </select>
                    <InputError :message="errors.color" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="3"
                        required
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="recommendations">Recommandations</Label>
                    <textarea
                        id="recommendations"
                        name="recommendations"
                        rows="3"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.recommendations" />
                </div>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link :href="editQuestionnaire(questionnaire.id)">
                            Annuler
                        </Link>
                    </Button>
                    <Button type="submit" :disabled="processing">
                        Créer
                    </Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
