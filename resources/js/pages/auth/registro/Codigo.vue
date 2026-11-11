<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const props = defineProps<{
    correo: string;
    reenviarEnSegundos: number;
}>();

defineOptions({
    layout: {
        title: 'Verificá tu correo',
        description: 'Escribí el código de 6 dígitos que te enviamos',
    },
});

const restante = ref(0);
let timer: ReturnType<typeof setInterval> | undefined;

const iniciarCuentaRegresiva = () => {
    restante.value = props.reenviarEnSegundos;

    if (timer) {
        clearInterval(timer);
    }

    timer = setInterval(() => {
        if (restante.value > 0) {
            restante.value--;
        } else if (timer) {
            clearInterval(timer);
        }
    }, 1000);
};

const reenviar = () => {
    router.post(
        '/registro/reenviar-codigo',
        {},
        {
            preserveScroll: true,
            onSuccess: () => iniciarCuentaRegresiva(),
        },
    );
};

onMounted(() => iniciarCuentaRegresiva());
onUnmounted(() => {
    if (timer) {
        clearInterval(timer);
    }
});
</script>

<template>
    <Head title="Código de verificación" />

    <div class="mb-4 text-center text-sm text-muted-foreground">
        Te enviamos un código a
        <span class="font-medium text-foreground">{{ correo }}</span>
    </div>

    <Form
        action="/registro/verificar-codigo"
        method="post"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="codigo">Código de 6 dígitos</Label>
                <Input
                    id="codigo"
                    type="text"
                    name="codigo"
                    inputmode="numeric"
                    maxlength="6"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="one-time-code"
                    placeholder="000000"
                />
                <InputError :message="errors.codigo" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="2"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Verificar
            </Button>
        </div>
    </Form>

    <div class="mt-4 text-center text-sm text-muted-foreground">
        ¿No te llegó?
        <button
            type="button"
            class="font-medium text-foreground underline underline-offset-4 disabled:no-underline disabled:opacity-50"
            :disabled="restante > 0"
            @click="reenviar"
        >
            Reenviar código{{ restante > 0 ? ` (${restante}s)` : '' }}
        </button>
    </div>

    <div class="mt-6 text-center text-sm text-muted-foreground">
        <TextLink href="/registro" :tabindex="3">Cambiar de correo</TextLink>
    </div>
</template>
