<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft, Pencil, Plus, Trash2 } from 'lucide-vue-next';
import { computed } from 'vue';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
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
    create as createOption,
    destroy as destroyOption,
    edit as editOption,
} from '@/routes/admin/questionnaires/questions/answer-options';
import type { BreadcrumbItem, Question, Questionnaire } from '@/types';

const props = defineProps<{
    questionnaire: Questionnaire;
    question: Question;
}>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Administration', href: adminDashboard() },
    { title: 'Questionnaires', href: indexQuestionnaires() },
    {
        title: props.questionnaire.title,
        href: editQuestionnaire(props.questionnaire.id),
    },
    {
        title: 'Question',
        href: editQuestionnaire(props.questionnaire.id),
    },
]);

const options = computed(() => props.question.answerOptions ?? []);

function confirmDelete(event: Event, message: string) {
    if (!window.confirm(message)) {
        event.preventDefault();
    }
}
</script>

<template>
    <Head title="Éditer la question" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 p-6">
            <Link
                :href="editQuestionnaire(questionnaire.id)"
                class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour au questionnaire
            </Link>

            <h1 class="text-2xl font-semibold tracking-tight">
                Éditer la question
            </h1>

            <Form
                v-bind="
                    QuestionController.update.form({
                        questionnaire: questionnaire.id,
                        question: question.id,
                    })
                "
                class="grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="text">Texte de la question</Label>
                    <textarea
                        id="text"
                        name="text"
                        rows="3"
                        required
                        :value="question.text"
                        class="rounded-md border border-input bg-background px-3 py-2 text-sm"
                    />
                    <InputError :message="errors.text" />
                </div>

                <div class="grid gap-2">
                    <Label for="position">Position</Label>
                    <Input
                        id="position"
                        name="position"
                        type="number"
                        min="0"
                        :default-value="question.position"
                    />
                    <InputError :message="errors.position" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_required"
                        value="1"
                        class="size-4 accent-primary"
                        :checked="question.is_required"
                    />
                    Réponse obligatoire
                </label>

                <div class="flex justify-end">
                    <Button type="submit" :disabled="processing">
                        Sauvegarder
                    </Button>
                </div>
            </Form>

            <section
                class="rounded-xl border border-border bg-card p-6 shadow-sm"
            >
                <header class="mb-4 flex items-center justify-between gap-3">
                    <h2 class="text-lg font-semibold">
                        Options de réponse ({{ options.length }})
                    </h2>
                    <Button as-child size="sm">
                        <Link
                            :href="
                                createOption([questionnaire.id, question.id])
                            "
                        >
                            <Plus class="size-4" />
                            Ajouter
                        </Link>
                    </Button>
                </header>

                <ul
                    v-if="options.length"
                    class="divide-y divide-border rounded-md border border-border"
                >
                    <li
                        v-for="option in options"
                        :key="option.id"
                        class="flex items-center justify-between gap-3 p-3"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ option.label }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                Position {{ option.position }}
                            </p>
                        </div>
                        <Badge variant="secondary">
                            {{ option.score }} pt{{
                                option.score > 1 ? 's' : ''
                            }}
                        </Badge>
                        <div class="inline-flex gap-1">
                            <Button size="icon" variant="ghost" as-child>
                                <Link
                                    :href="
                                        editOption([
                                            questionnaire.id,
                                            question.id,
                                            option.id,
                                        ])
                                    "
                                >
                                    <Pencil class="size-4" />
                                </Link>
                            </Button>
                            <Link
                                :href="
                                    destroyOption([
                                        questionnaire.id,
                                        question.id,
                                        option.id,
                                    ]).url
                                "
                                method="delete"
                                as="button"
                                class="inline-flex size-9 items-center justify-center rounded-md text-red-600 hover:bg-red-50 hover:text-red-700 dark:hover:bg-red-950"
                                @click="
                                    (event) =>
                                        confirmDelete(
                                            event,
                                            'Supprimer cette option ?',
                                        )
                                "
                            >
                                <Trash2 class="size-4" />
                            </Link>
                        </div>
                    </li>
                </ul>
                <p v-else class="text-sm text-muted-foreground italic">
                    Aucune option. Ajoutez-en au moins deux pour permettre une
                    réponse.
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
