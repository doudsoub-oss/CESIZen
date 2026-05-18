<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import PublicLayout from '@/layouts/PublicLayout.vue';
import { submit as submitRoute } from '@/routes/diagnostic';
import type { Questionnaire } from '@/types';

const props = defineProps<{
    questionnaire: Questionnaire;
}>();

const questions = computed(() => props.questionnaire.questions ?? []);

// answers: { [questionId]: answerOptionId | null }
const initialAnswers: Record<number, number | null> = Object.fromEntries(
    questions.value.map((q) => [q.id, null]),
);

const form = useForm<{ answers: Record<number, number | null> }>({
    answers: initialAnswers,
});

const answeredCount = computed(
    () => Object.values(form.answers).filter((v) => v !== null).length,
);

const total = computed(() => questions.value.length);

const allAnswered = computed(() => answeredCount.value === total.value);

const questionRefs = ref<Record<number, HTMLElement | null>>({});

function setQuestionRef(qid: number, el: Element | null) {
    questionRefs.value[qid] = (el as HTMLElement) ?? null;
}

function focusFirstUnanswered() {
    const first = questions.value.find((q) => form.answers[q.id] === null);

    if (first && questionRefs.value[first.id]) {
        questionRefs.value[first.id]?.scrollIntoView({
            behavior: 'smooth',
            block: 'center',
        });
    }
}

function onSubmit() {
    if (!allAnswered.value) {
        focusFirstUnanswered();

        return;
    }

    form.post(submitRoute({ slug: props.questionnaire.slug }).url, {
        preserveScroll: false,
    });
}

function errorFor(qid: number): string | undefined {
    const key = `answers.${qid}` as keyof typeof form.errors;

    return form.errors[key] as string | undefined;
}
</script>

<template>
    <Head :title="questionnaire.title" />

    <PublicLayout>
        <div
            class="sticky top-0 z-10 border-b border-border bg-background/95 backdrop-blur"
        >
            <div
                class="mx-auto flex w-full max-w-3xl items-center justify-between gap-4 px-4 py-3"
            >
                <h1 class="truncate text-lg font-semibold">
                    {{ questionnaire.title }}
                </h1>
                <div class="flex items-center gap-3 text-sm">
                    <span class="font-medium">
                        {{ answeredCount }} / {{ total }}
                    </span>
                    <div
                        class="hidden h-2 w-48 overflow-hidden rounded-full bg-muted sm:block"
                        :aria-label="`Progression : ${answeredCount} sur ${total}`"
                        role="progressbar"
                        :aria-valuenow="answeredCount"
                        :aria-valuemin="0"
                        :aria-valuemax="total"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{
                                width: `${(answeredCount / total) * 100}%`,
                            }"
                        />
                    </div>
                </div>
            </div>
        </div>

        <form
            class="mx-auto w-full max-w-3xl px-4 py-8"
            @submit.prevent="onSubmit"
        >
            <p
                v-if="questionnaire.instructions"
                class="mb-6 rounded-md border border-border bg-muted/40 p-4 text-sm text-muted-foreground"
            >
                {{ questionnaire.instructions }}
            </p>

            <ol class="flex flex-col gap-6">
                <li
                    v-for="(question, idx) in questions"
                    :key="question.id"
                    :ref="(el) => setQuestionRef(question.id, el as Element)"
                    class="rounded-xl border border-border bg-card p-5 shadow-sm"
                >
                    <fieldset>
                        <legend class="flex items-start gap-3 font-medium">
                            <span
                                class="inline-flex size-7 shrink-0 items-center justify-center rounded-full bg-primary/10 text-sm font-semibold text-primary"
                            >
                                {{ idx + 1 }}
                            </span>
                            <span>{{ question.text }}</span>
                        </legend>

                        <div class="mt-4 grid gap-2 pl-10">
                            <label
                                v-for="option in question.answerOptions ?? []"
                                :key="option.id"
                                class="flex cursor-pointer items-center gap-3 rounded-md border border-transparent px-3 py-2 hover:border-border has-checked:border-primary has-checked:bg-primary/5"
                            >
                                <input
                                    type="radio"
                                    :name="`q-${question.id}`"
                                    :value="option.id"
                                    v-model="form.answers[question.id]"
                                    class="size-4 accent-primary"
                                />
                                <span class="text-sm">{{ option.label }}</span>
                            </label>
                        </div>

                        <InputError
                            class="mt-2 pl-10"
                            :message="errorFor(question.id)"
                        />
                    </fieldset>
                </li>
            </ol>

            <div
                class="mt-8 flex flex-col items-stretch gap-3 sm:flex-row sm:items-center sm:justify-end"
            >
                <p
                    v-if="!allAnswered"
                    class="text-sm text-muted-foreground sm:mr-auto"
                >
                    Veuillez répondre à toutes les questions ({{
                        total - answeredCount
                    }}
                    restante{{ total - answeredCount > 1 ? 's' : '' }}).
                </p>
                <Button
                    type="submit"
                    size="lg"
                    :disabled="form.processing || !allAnswered"
                >
                    Voir mon résultat
                </Button>
            </div>
        </form>
    </PublicLayout>
</template>
