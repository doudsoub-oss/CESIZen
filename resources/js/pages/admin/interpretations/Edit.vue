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
import type {
    BreadcrumbItem,
    Questionnaire,
    ResultInterpretation,
} from '@/types';

const props = defineProps<{
    questionnaire: Questionnaire;
    interpretation: ResultInterpretation;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Questionnaires', href: indexQuestionnaires() },
    {
        title: props.questionnaire.title,
        href: editQuestionnaire(props.questionnaire.id),
    },
    {
        title: props.interpretation.title,
        href: editQuestionnaire(props.questionnaire.id),
    },
]);
</script>

<template>
    <Head title="Éditer l'interprétation" />

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
                Éditer l'interprétation
            </h1>

            <Form
                v-bind="
                    ResultInterpretationController.update.form({
                        questionnaire: questionnaire.id,
                        interpretation: interpretation.id,
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
                            :default-value="interpretation.min_score"
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
                            :default-value="interpretation.max_score"
                        />
                        <InputError :message="errors.max_score" />
                    </div>
                </div>

                <div class="grid gap-2">
                    <Label for="title">Titre</Label>
                    <Input
                        id="title"
                        name="title"
                        required
                        :default-value="interpretation.title"
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="color">Couleur</Label>
                    <select
                        id="color"
                        name="color"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    >
                        <option value="" :selected="!interpretation.color">
                            — Aucune —
                        </option>
                        <option
                            value="green"
                            :selected="interpretation.color === 'green'"
                        >
                            Vert
                        </option>
                        <option
                            value="yellow"
                            :selected="interpretation.color === 'yellow'"
                        >
                            Jaune
                        </option>
                        <option
                            value="red"
                            :selected="interpretation.color === 'red'"
                        >
                            Rouge
                        </option>
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
                        :value="interpretation.description"
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
                        :value="interpretation.recommendations ?? ''"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.recommendations" />
                </div>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        Sauvegarder
                    </Button>
                </div>
            </Form>
        </div>
    </AdminLayout>
</template>
