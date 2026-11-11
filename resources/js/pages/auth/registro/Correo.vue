<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

defineOptions({
    layout: {
        title: 'Crear una cuenta',
        description: 'Ingresá tu correo institucional para empezar',
    },
});
</script>

<template>
    <Head title="Registro" />

    <Form
        action="/registro/enviar-codigo"
        method="post"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-6"
    >
        <div class="grid gap-6">
            <div class="grid gap-2">
                <Label for="correo">Correo institucional</Label>
                <Input
                    id="correo"
                    type="email"
                    name="correo"
                    required
                    v-focus
                    :tabindex="1"
                    autocomplete="email"
                    placeholder="usuario@unicah.edu"
                />
                <InputError :message="errors.correo" />
            </div>

            <Button
                type="submit"
                class="mt-2 w-full"
                :tabindex="2"
                :disabled="processing"
            >
                <Spinner v-if="processing" />
                Enviar código
            </Button>
        </div>

        <div class="text-center text-sm text-muted-foreground">
            ¿Ya tenés una cuenta?
            <TextLink href="/login" class="underline underline-offset-4" :tabindex="3">
                Iniciá sesión
            </TextLink>
        </div>
    </Form>
</template>
