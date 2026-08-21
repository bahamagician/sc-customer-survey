<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import ChoiceList from '@/components/survey/ChoiceList.vue';
import LikertScale from '@/components/survey/LikertScale.vue';
import MultiSelectChips from '@/components/survey/MultiSelectChips.vue';
import ScaleOneTen from '@/components/survey/ScaleOneTen.vue';
import SurveyProgress from '@/components/survey/SurveyProgress.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { answer, question as questionRoute } from '@/routes/survey';

interface SurveyQuestion {
    key: string;
    type: string;
    preamble: string | null;
    label: string;
    options: Record<string, string>;
    optional: boolean;
    hint: string | null;
    followUpLabel: string | null;
}

interface ContactValue {
    name: string;
    email: string;
    follow_up_requested: boolean;
}

const props = defineProps<{
    responseId: string;
    question: SurveyQuestion;
    value: unknown;
    progress: { position: number; total: number };
    previousKey: string | null;
    errors: Record<string, string>;
}>();

const processing = ref(false);

const numberValue = ref<number | null>(null);
const stringValue = ref<string | null>(null);
const listValue = ref<string[]>([]);
const otherText = ref<string>('');
const textValue = ref<string>('');
const contactValue = ref<ContactValue>({
    name: '',
    email: '',
    follow_up_requested: false,
});

function hydrateFromProps(): void {
    const value = props.value;

    numberValue.value = typeof value === 'number' ? value : null;
    stringValue.value = typeof value === 'string' ? value : null;
    textValue.value = typeof value === 'string' ? value : '';
    listValue.value = Array.isArray(value) ? (value as string[]) : [];
    otherText.value = '';

    if (
        props.question.type === 'multi_select_other' &&
        value &&
        typeof value === 'object'
    ) {
        const saved = value as { selected?: string[]; other?: string | null };
        listValue.value = saved.selected ?? [];
        otherText.value = saved.other ?? '';
    }

    if (props.question.type === 'contact') {
        const saved = (value ?? {}) as {
            name?: string | null;
            email?: string | null;
            follow_up_requested?: boolean;
        };
        contactValue.value = {
            name: saved.name ?? '',
            email: saved.email ?? '',
            follow_up_requested: saved.follow_up_requested ?? false,
        };
    }
}

hydrateFromProps();
watch(() => props.question.key, hydrateFromProps);

function submit(value: unknown): void {
    router.patch(
        answer.url({
            response: props.responseId,
            question: props.question.key,
        }),
        // contact_time is a honeypot — humans never see it, bots fill it.
        { value: value as never, contact_time: '' },
        {
            onStart: () => (processing.value = true),
            onFinish: () => (processing.value = false),
        },
    );
}

function submitMultiSelectOther(): void {
    submit({
        selected: listValue.value,
        other: listValue.value.includes('other') ? otherText.value : null,
    });
}
</script>

<template>
    <div class="flex flex-1 flex-col gap-6">
        <Head title="Customer Survey" />

        <header class="grid gap-4">
            <div class="flex min-h-9 items-center">
                <Button v-if="previousKey" variant="ghost" size="sm" as-child>
                    <Link
                        :href="
                            questionRoute.url({
                                response: responseId,
                                question: previousKey,
                            })
                        "
                    >
                        <ChevronLeft class="size-4" />
                        Back
                    </Link>
                </Button>
            </div>
            <SurveyProgress
                :position="progress.position"
                :total="progress.total"
            />
        </header>

        <div class="grid gap-1">
            <h1 class="text-xl leading-snug font-semibold">
                <span v-if="question.preamble" class="block italic">{{
                    question.preamble
                }}</span>
                {{ question.label }}
                <span
                    v-if="question.optional"
                    class="block text-sm font-normal text-muted-foreground"
                    >Optional</span
                >
            </h1>
            <p v-if="question.hint" class="text-sm text-muted-foreground">
                {{ question.hint }}
            </p>
        </div>

        <div class="grid gap-4">
            <LikertScale
                v-if="question.type === 'likert'"
                :model-value="numberValue"
                :disabled="processing"
                @select="submit($event)"
            />

            <ScaleOneTen
                v-else-if="question.type === 'scale_1_10'"
                :model-value="numberValue"
                :disabled="processing"
                @select="submit($event)"
            />

            <ChoiceList
                v-else-if="question.type === 'single_choice'"
                :options="question.options"
                :model-value="stringValue"
                :disabled="processing"
                @select="submit($event)"
            />

            <template v-else-if="question.type === 'multi_select'">
                <MultiSelectChips
                    v-model="listValue"
                    :options="question.options"
                />
                <Button
                    class="min-h-12"
                    :disabled="processing || listValue.length === 0"
                    @click="submit(listValue)"
                >
                    <Spinner v-if="processing" />
                    Next
                </Button>
            </template>

            <template v-else-if="question.type === 'multi_select_other'">
                <MultiSelectChips
                    v-model="listValue"
                    :options="{ ...question.options, other: 'Other' }"
                />
                <div v-if="listValue.includes('other')" class="grid gap-2">
                    <Label for="other-text">Tell us more</Label>
                    <Input
                        id="other-text"
                        v-model="otherText"
                        type="text"
                        maxlength="255"
                    />
                    <InputError :message="errors['value.other']" />
                </div>
                <Button
                    class="min-h-12"
                    :disabled="processing || listValue.length === 0"
                    @click="submitMultiSelectOther"
                >
                    <Spinner v-if="processing" />
                    Next
                </Button>
            </template>

            <template v-else-if="question.type === 'text'">
                <Textarea
                    v-model="textValue"
                    rows="4"
                    maxlength="2000"
                    :placeholder="'Your thoughts…'"
                />
                <Button
                    class="min-h-12"
                    :disabled="processing"
                    @click="submit(textValue.trim() === '' ? null : textValue)"
                >
                    <Spinner v-if="processing" />
                    Next
                </Button>
                <Button
                    variant="ghost"
                    :disabled="processing"
                    @click="submit(null)"
                >
                    Skip
                </Button>
            </template>

            <template v-else-if="question.type === 'contact'">
                <div class="grid gap-2">
                    <Label for="contact-name">Name</Label>
                    <Input
                        id="contact-name"
                        v-model="contactValue.name"
                        type="text"
                        autocomplete="name"
                    />
                    <InputError :message="errors['value.name']" />
                </div>
                <div class="grid gap-2">
                    <Label for="contact-email">Email</Label>
                    <Input
                        id="contact-email"
                        v-model="contactValue.email"
                        type="email"
                        autocomplete="email"
                    />
                    <InputError :message="errors['value.email']" />
                </div>
                <Label
                    v-if="question.followUpLabel"
                    class="flex min-h-11 items-start gap-3 text-base font-normal"
                >
                    <Checkbox
                        v-model="contactValue.follow_up_requested"
                        class="mt-0.5"
                    />
                    {{ question.followUpLabel }}
                </Label>
                <Button
                    class="min-h-12"
                    :disabled="processing"
                    @click="submit(contactValue)"
                >
                    <Spinner v-if="processing" />
                    Finish
                </Button>
            </template>

            <InputError :message="errors.value" />
        </div>
    </div>
</template>
