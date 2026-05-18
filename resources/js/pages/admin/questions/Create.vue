<script setup lang="ts">
import { Form, Head, Link } from '@inertiajs/vue3';
import { ChevronLeft } from 'lucide-vue-next';
import { computed } from 'vue';
import QuestionController from '@/actions/App/Http/Controllers/Admin/QuestionController';
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
        title: 'Nouvelle question',
        href: editQuestionnaire(props.questionnaire.id),
    },
]);
</script>

<template>
    <Head title="Nouvelle question" />

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
                Ajouter une question
            </h1>
            <p class="mt-1 text-sm text-muted-foreground">
                à « {{ questionnaire.title }} »
            </p>

            <Form
                v-bind="
                    QuestionController.store.form({
                        questionnaire: questionnaire.id,
                    })
                "
                class="mt-6 grid gap-4 rounded-xl border border-border bg-card p-6 shadow-sm"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-2">
                    <Label for="text">Texte de la question</Label>
                    <textarea
                        id="text"
                        name="text"
                        rows="3"
                        required
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
                        :default-value="0"
                    />
                    <InputError :message="errors.position" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        type="checkbox"
                        name="is_required"
                        value="1"
                        class="size-4 accent-primary"
                        checked
                    />
                    Réponse obligatoire
                </label>

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
