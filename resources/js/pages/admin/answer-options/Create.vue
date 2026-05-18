<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import AnswerOptionController from '@/actions/App/Http/Controllers/Admin/AnswerOptionController';
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
import { edit as editQuestion } from '@/routes/admin/questionnaires/questions';
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
        href: editQuestion([props.questionnaire.id, props.question.id]),
    },
    {
        title: 'Nouvelle option',
        href: editQuestion([props.questionnaire.id, props.question.id]),
    },
]);
</script>

<template>
    <Head title="Nouvelle option" />

    <AdminLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto w-full max-w-xl p-6">
            <Link
                :href="editQuestion([questionnaire.id, question.id])"
                class="mb-4 inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ChevronLeft class="size-4" />
                Retour à la question
            </Link>

            <h1 class="text-2xl font-semibold tracking-tight">
                Ajouter une option
            </h1>

            <Form
                v-bind="
                    AnswerOptionController.store.form({
                        questionnaire: questionnaire.id,
                        question: question.id,
                    })
                "
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="label">Libellé</Label>
                    <Input id="label" name="label" required />
                    <InputError :message="errors.label" />
                </div>

                <div class="grid gap-2">
                    <Label for="score">Score</Label>
                    <Input
                        id="score"
                        name="score"
                        type="number"
                        required
                        :default-value="0"
                    />
                    <InputError :message="errors.score" />
                </div>

                <div class="grid gap-2">
                    <Label for="position">Position</Label>
                    <Input
                        id="position"
                        name="position"
                        type="number"
                        min="0"
                        :default-value="0"
                    />
                    <InputError :message="errors.position" />
                </div>

                <div class="flex justify-end gap-2">
                    <Button as-child variant="ghost">
                        <Link
                            :href="
                                editQuestion([questionnaire.id, question.id])
                            "
                        >
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
