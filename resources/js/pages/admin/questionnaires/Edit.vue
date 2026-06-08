<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import QuestionnaireController from '@/actions/App/Http/Controllers/Admin/QuestionnaireController';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AdminLayout from '@/layouts/AdminLayout.vue';
import { dashboard as adminDashboard } from '@/routes/admin';
import {
    edit as editQuestionnaire,
    index as indexQuestionnaires,
} from '@/routes/admin/questionnaires';
import {
    create as createInterpretation,
    destroy as destroyInterpretation,
    edit as editInterpretation,
} from '@/routes/admin/questionnaires/interpretations';
import {
    create as createQuestion,
    destroy as destroyQuestion,
    edit as editQuestion,
} from '@/routes/admin/questionnaires/questions';
import type {
    BreadcrumbItem,
    Questionnaire,
    ResultInterpretation,
} from '@/types';

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
]);

const questions = computed(() => props.questionnaire.questions ?? []);
const interpretations = computed(
    () => props.questionnaire.interpretations ?? [],
);

const rangeMin = computed(() =>
    interpretations.value.length
        ? Math.min(...interpretations.value.map((i) => i.min_score))
        : 0,
);
const rangeMax = computed(() =>
    interpretations.value.length
        ? Math.max(...interpretations.value.map((i) => i.max_score))
        : 100,
);
const rangeSpan = computed(() => Math.max(1, rangeMax.value - rangeMin.value));

function widthPct(interp: ResultInterpretation): string {
    const width =
        ((interp.max_score - interp.min_score + 1) / rangeSpan.value) * 100;

    return `${Math.max(2, width)}%`;
}

function leftPct(interp: ResultInterpretation): string {
    const left = ((interp.min_score - rangeMin.value) / rangeSpan.value) * 100;

    return `${left}%`;
}

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}

function colorBg(color: string | null): string {
    switch (color) {
        case 'green':
            return 'bg-emerald-500';
        case 'yellow':
            return 'bg-amber-500';
        case 'red':
            return 'bg-red-500';
        default:
            return 'bg-slate-400';
    }
}
</script>

<template>
    <Head :title="`Éditer ${questionnaire.title}`" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-4xl flex-col gap-6 p-6">
            <Link
                :href="indexQuestionnaires()"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la liste
            </Link>

            <header>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Éditer le questionnaire
                </h1>
            </header>

            <!-- Questionnaire fields -->
            <Form
                v-bind="
                    QuestionnaireController.update.form({
                        questionnaire: questionnaire.id,
                    })
                "
                class="grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="title">Titre</Label>
                    <Input
                        id="title"
                        name="title"
                        :default-value="questionnaire.title"
                        required
                    />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="slug">Slug</Label>
                    <Input
                        id="slug"
                        name="slug"
                        :default-value="questionnaire.slug"
                        required
                    />
                    <InputError :message="errors.slug" />
                </div>

                <div class="grid gap-2">
                    <Label for="description">Description</Label>
                    <textarea
                        id="description"
                        name="description"
                        rows="2"
                        :value="questionnaire.description ?? ''"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.description" />
                </div>

                <div class="grid gap-2">
                    <Label for="instructions">Instructions</Label>
                    <textarea
                        id="instructions"
                        name="instructions"
                        rows="4"
                        :value="questionnaire.instructions ?? ''"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.instructions" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        class="size-4 accent-primary"
                        :checked="questionnaire.is_active"
                    />
                    Actif
                </label>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        Sauvegarder
                    </Button>
                </div>
            </Form>

            <!-- Questions panel -->
            <section
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <header class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">
                        Questions ({{ questions.length }})
                    </h2>
                    <Button as-child size="sm">
                        <Link :href="createQuestion(questionnaire.id)">
                            <Plus class="size-4" />
                            Ajouter
                        </Link>
                    </Button>
                </header>

                <ul
                    v-if="questions.length"
                    class="divide-y divide-border rounded-md border border-border"
                >
                    <li
                        v-for="(question, idx) in questions"
                        :key="question.id"
                        class="flex items-center justify-between gap-3 p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ idx + 1 }}. {{ question.text }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ (question.answer_options ?? []).length }}
                                option{{
                                    (question.answer_options ?? []).length > 1
                                        ? 's'
                                        : ''
                                }}
                            </p>
                        </div>
                        <div class="inline-flex shrink-0 gap-1">
                            <Button size="icon" variant="ghost" as-child>
                                <Link
                                    :href="
                                        editQuestion([
                                            questionnaire.id,
                                            question.id,
                                        ])
                                    "
                                >
                                    <Pencil class="size-4" />
                                </Link>
                            </Button>
                            <Link
                                :href="
                                    destroyQuestion([
                                        questionnaire.id,
                                        question.id,
                                    ]).url
                                "
                                method="delete"
                                as="button"
                                class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                @click="
                                    (event) =>
                                        confirmDelete(
                                            event,
                                            'Supprimer cette question ?',
                                        )
                                "
                            >
                                <Trash2 class="size-4" />
                            </Link>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground italic">
                    Aucune question. Ajoutez-en une pour démarrer.
                </p>
            </section>

            <!-- Interpretations panel -->
            <section
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <header class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">
                        Interprétations ({{ interpretations.length }})
                    </h2>
                    <Button as-child size="sm">
                        <Link :href="createInterpretation(questionnaire.id)">
                            <Plus class="size-4" />
                            Ajouter
                        </Link>
                    </Button>
                </header>

                <!-- Range bar -->
                <div
                    v-if="interpretations.length"
                    class="mb-4 rounded-md border border-border p-3"
                >
                    <div class="relative h-6 rounded bg-muted">
                        <div
                            v-for="interp in interpretations"
                            :key="`bar-${interp.id}`"
                            :class="[
                                'absolute top-0 h-full rounded-sm border border-white/70',
                                colorBg(interp.color),
                            ]"
                            :style="{
                                left: leftPct(interp),
                                width: widthPct(interp),
                            }"
                            :title="`${interp.title} (${interp.min_score}–${interp.max_score})`"
                        />
                    </div>
                    <div
                        class="mt-1 flex justify-between text-xs text-muted-foreground"
                    >
                        <span>{{ rangeMin }}</span>
                        <span>{{ rangeMax }}</span>
                    </div>
                </div>

                <ul
                    v-if="interpretations.length"
                    class="divide-y divide-border rounded-md border border-border"
                >
                    <li
                        v-for="interp in interpretations"
                        :key="interp.id"
                        class="flex items-center justify-between gap-3 p-3"
                    >
                        <div class="min-w-0">
                            <p
                                class="flex items-center gap-2 text-sm font-medium"
                            >
                                <span
                                    class="inline-block size-3 rounded-full"
                                    :class="colorBg(interp.color)"
                                    aria-hidden="true"
                                />
                                {{ interp.title }}
                                <Badge variant="secondary">
                                    {{ interp.min_score }}–{{
                                        interp.max_score
                                    }}
                                </Badge>
                            </p>
                            <p
                                class="line-clamp-2 text-xs text-muted-foreground"
                            >
                                {{ interp.description }}
                            </p>
                        </div>
                        <div class="inline-flex shrink-0 gap-1">
                            <Button size="icon" variant="ghost" as-child>
                                <Link
                                    :href="
                                        editInterpretation([
                                            questionnaire.id,
                                            interp.id,
                                        ])
                                    "
                                >
                                    <Pencil class="size-4" />
                                </Link>
                            </Button>
                            <Link
                                :href="
                                    destroyInterpretation([
                                        questionnaire.id,
                                        interp.id,
                                    ]).url
                                "
                                method="delete"
                                as="button"
                                class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                @click="
                                    (event) =>
                                        confirmDelete(
                                            event,
                                            'Supprimer cette interprétation ?',
                                        )
                                "
                            >
                                <Trash2 class="size-4" />
                            </Link>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground italic">
                    Aucune interprétation. Ajoutez-en au moins une pour fournir
                    un résultat aux utilisateurs.
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
